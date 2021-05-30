<?php


namespace Drupal\fjcom\Form;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form in the admin UI for test purpose.
 *
 * @package Drupal\fjcom\Form
 */
class SearchConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fjcom.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fjcom_search_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fjcom.settings');

    $form['site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('Site URL description'),
      '#required' => TRUE,
      '#default_value' => $config->get('site_url'),
    ];

    $form['search_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search title'),
      '#description' => $this->t('Search title description'),
      '#required' => TRUE,
      '#default_value' => $config->get('search_title'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $site_url = $form_state->getValue('site_url');
    if (!UrlHelper::isValid($site_url, TRUE)) {
      $form_state->setErrorByName('site_url', $this->t('Check that the URL is valid.'));
    }
    if (parse_url($site_url, PHP_URL_SCHEME) != 'https') {
      $form_state->setErrorByName('site_url', $this->t('Check that the URL is contains HTTPS.'));
    }
    if (mb_substr($site_url, -1) == '/') {
      $form_state->setErrorByName('site_url', $this->t('Check that the URL without last slash character.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fjcom.settings')
      ->set('site_url', $form_state->getValue('site_url'))
      ->set('search_title', $form_state->getValue('search_title'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}