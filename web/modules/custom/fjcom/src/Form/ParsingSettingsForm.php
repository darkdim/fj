<?php


namespace Drupal\fjcom\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a parsing settings form.
 *
 * @package Drupal\fjcom\Form
 */
class ParsingSettingsForm extends FormBase {

  public function getFormId() {
    return 'fjcom_parsing_settings_form';
  }

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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fjcom.settings');

    $http_client = \Drupal::httpClient();
    $response = $http_client->request('GET', 'https://fivejars.com/sitemap.xml');
    $status_code = $response->getStatusCode();
  }

}