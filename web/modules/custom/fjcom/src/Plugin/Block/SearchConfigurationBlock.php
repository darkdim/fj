<?php


namespace Drupal\fjcom\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\fjcom\Form\ParsingSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Search configuration' block.
 *
 * @Block(
 *   id = "fjcom_search_configuration",
 *   admin_label = @Translation("Search configuration"),
 *   category = @Translation("Forms"),
 * )
 *
 * @package Drupal\fjcom\Plugin\Block
 */
class SearchConfigurationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * SearchConfigurationBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\fjcom\Plugin\Block\SearchConfigurationBlock|static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'form' => $this->formBuilder->getForm(ParsingSettingsForm::class),
    ];
  }

}
