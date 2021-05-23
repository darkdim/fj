<?php


namespace Drupal\fjcom\Pllugin\Block;


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
 * @package Drupal\fjcom\Pllugin\Block
 */
class SearchConfigurationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function build() {
    // TODO: Implement build() method.
  }

}