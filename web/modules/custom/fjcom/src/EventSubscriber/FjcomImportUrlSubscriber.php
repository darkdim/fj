<?php


namespace Drupal\fjcom\EventSubscriber;


use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\fjcom\ImportUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class FjcomImportUrlSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FjcomImportUrlSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ImportUrlEvent::EVENT][] = ['onImport', 0];
    return $events;
  }

  /**
   * Send email if title match.
   *
   * @param \Drupal\fjcom\ImportUrlEvent $event
   */
  public function onImport(ImportUrlEvent $event) {
    $config = $this->configFactory->get('fjcom.settings');
    if ($event->getObject()->getTitle() == $config->get('search_title')) {
      $to = $this->configFactory->get('system.site')->get('mail');
      $langcode = $this->configFactory->get('system.site')->get('langcode');
      $markup = new FormattableMarkup('Article title matches the search title: @title', ['@title' => $event->getObject()->getTitle()]);
      \Drupal::service('plugin.manager.mail')->mail('fjcom', 'fjcom_importUrl', $to, $langcode, ['message' => $markup]);
    }
  }

}
