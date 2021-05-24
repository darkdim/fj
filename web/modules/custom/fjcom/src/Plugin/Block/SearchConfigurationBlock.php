<?php


namespace Drupal\fjcom\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * SearchConfigurationBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\fjcom\Form\ParsingSettingsForm');

    return $form;
  }

}
