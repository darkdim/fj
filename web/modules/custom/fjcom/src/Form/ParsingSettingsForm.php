<?php


namespace Drupal\fjcom\Form;


use Drupal\Component\Utility\Html;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fjcom\URLBatchImport;
use Drupal\node\Entity\Node;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Provides a parsing settings form.
 *
 * @package Drupal\fjcom\Form
 */
class ParsingSettingsForm extends FormBase {

  /**
   * @var \Symfony\Contracts\HttpClient\HttpClientInterface
   */
  protected $httpClient;

  /**
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  protected $entityTypeManager;

  /**
   * ParsingSettingsForm constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client;
    $this->batchBuilder = new BatchBuilder();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fjcom_parsing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('Pattern description'),
    ];
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#description' => $this->t('Limit description'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fjcom.settings');

    $uri = $config->get('site_url') . '/sitemap.xml';
    $response = $this->getResponse($uri);
    if ($response->getStatusCode() != 200) {
      $form_state->setError($form, $this->t('Error http status.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pattern = $form_state->getValue('pattern');
    $limit = (int) $form_state->getValue('limit');
    $data = (string) $this->response->getBody();
    $feeds = $this->filterFeeds($this->parseSitemap($data), $pattern, $limit);
    if (empty($feeds)) {
      $form_state->setError($form, $this->t('Result is empty.'));
    }
    $this->batchBuilder
      ->setTitle($this->t('Importing URL'))
      ->setInitMessage($this->t('Initializing'))
      ->setProgressMessage($this->t('Completed @current of @total'))
      ->setErrorMessage($this->t('An error has occurred'));
    $this->batchBuilder->setFinishCallback([$this, 'importUrlsFinished']);
    $this->batchBuilder->addOperation([$this, 'importUrls'], [$feeds]);

    batch_set($this->batchBuilder->toArray());
  }

  /**
   * Batch operation to import the urls.
   *
   * @param $feeds
   * @param array $context
   */
  public function importUrls($feeds, array &$context) {
    if (!isset($context['results']['imported'])) {
      $context['results']['imported'] = [];
    }

    if (!$feeds) {
      return;
    }

    $sandbox = &$context['sandbox'];
    if (!$sandbox) {
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($feeds);
      $sandbox['feeds'] = $feeds;
    }

    $slice = array_splice($sandbox['feeds'], 0, 1);
    foreach ($slice as $feed) {
      $context['message'] = $this->t('Importing url @url', ['@url' => $feed]);
      $this->importUrl($feed);
      $context['results']['imported'][] = $feed;
      $sandbox['progress']++;
    }

    $context['finished'] = $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * @param string $feed
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importUrl(string $feed) {
    $html = file_get_contents($feed);
    $dom = Html::load($html);
    $title = Html::escape($this->getTagValue($dom, 'title'));
    $meta_tags = get_meta_tags($feed);
    $description = Html::escape($meta_tags['description']);
    $twitter_image = explode('/', $meta_tags['twitter:image']);
    $image_name = array_pop($twitter_image);
    $file = file_save_data(file_get_contents($meta_tags['twitter:image']), 'public://' . substr($image_name, 0, strpos($image_name, '?')));
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->create([
      'type' => 'article',
      'title' => $title,
      'body' => [
        'value' => $description,
        'format' => 'full_html',
      ],
      'field_image' => [
        'target_id' => $file->id(),
        'alt' => $title,
        'title' => $title,
      ],
    ]);
    $node->save();
  }

  /**
   * @param \DOMDocument $dom
   * @param string $tag
   *
   * @return string
   */
  protected function getTagValue(\DOMDocument $dom, string $tag) {
    $tag = $dom->getElementsByTagName($tag);
    if ($tag->length) {
      return $tag->item(0)->nodeValue;
    }
  }

  /**
   * Callback for when the batch processing completes.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public function importUrlsFinished($success, $results, $operations) {
    if (!$success) {
      $this->messenger->addStatus($this->t('There was a problem with the batch'), 'error');
      return;
    }

    $imported = count($results['imported']);
    if ($imported == 0) {
      $this->messenger()->addStatus($this->t('No urls found to be imported.'));
    }
    else {
      $this->messenger()->addStatus($this->formatPlural($imported, '1 url imported.', '@count urls imported.'));
    }
  }

  /**
   * @param mixed $response
   */
  public function setResponse($response): void {
    $this->response = $response;
  }

  /**
   * @param array $feeds
   * @param string $pattern
   * @param int $limit
   *
   * @return array
   */
  protected function filterFeeds(array $feeds, string $pattern, int $limit = 0) {
    $pattern .= '/';
    $output = [];
    foreach ($feeds as $item) {
      if (strpos($item, $pattern) !== FALSE) {
        if (isset(explode($pattern, $item)[1])) {
          $output[] = $item;
        }
      }
    }
    sort($output);
    if ($limit) {
      $output = array_slice($output, 0, $limit);
    }

    return $output;
  }

  /**
   * @param string $data
   *
   * @return array
   */
  protected function parseSitemap(string $data) {
    $feeds = [];
    $xml_parser = xml_parser_create();
    xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
    if (xml_parse_into_struct($xml_parser, $data, $values)) {
      foreach ($values as $entry) {
        if ($entry['tag'] == 'LOC' && isset($entry['value'])) {
          $feeds[] = $entry['value'];
        }
      }
    }
    xml_parser_free($xml_parser);

    return $feeds;
  }

  /**
   * @param string $uri
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getResponse(string $uri) {
    if (!$this->response) {
      try {
        $response = $this->httpClient->request('GET', $uri);
        $this->setResponse($response);
      } catch (GuzzleException $e) {
      } catch (TransportExceptionInterface $e) {
      }
    }
    return $this->response;
  }

}