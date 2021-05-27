<?php


namespace Drupal\fjcom\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

  protected $response;

  /**
   * ParsingSettingsForm constructor.
   *
   * @param \Symfony\Contracts\HttpClient\HttpClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
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
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fjcom.settings');

    $uri = $config->get('site_url') . '/sitemap.xml';
    try {
      $response = $this->httpClient->request('GET', $uri);
      $this->setResponse($response);
    } catch (GuzzleException $e) {
    } catch (TransportExceptionInterface $e) {
    }
    if ($response->getStatusCode() != 200) {
      $form_state->setError($form, $this->t('Error http status.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = (string) $this->response->getBody();
    $feeds = $this->parseSitemap($data);
  }

  /**
   * @param mixed $response
   */
  public function setResponse($response): void {
    $this->response = $response;
  }

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

}