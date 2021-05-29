<?php


namespace Drupal\fjcom;


use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event class to be dispatched after saving the node from ImportUrl.
 *
 * @package Drupal\fjcom
 */
class ImportUrlEvent extends Event {

  const EVENT = 'fjcom.import_url_event';

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $object;

  /**
   * @param \Drupal\node\NodeInterface $object
   */
  public function setObject(NodeInterface $object) {
    $this->object = $object;
  }

  /**
   * @return \Drupal\node\NodeInterface
   */
  public function getObject() {
    return $this->object;
  }

}
