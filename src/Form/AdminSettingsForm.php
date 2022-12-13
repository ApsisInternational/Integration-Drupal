<?php

namespace Drupal\apsisone\Form;

use Drupal\apsisone\ApsisoneService;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Administration page callbacks for the Apsis One module.
 */

/**
 * Settings form for Apsisone.
 *
 * @package Drupal\apsisone\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apsisone_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'apsisone.settings',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('apsisone.settings');

    $form['connection'] = [
      '#type' => 'details',
      '#title' => $this->t('Connection'),
      '#description' => $this->t('Fill in the form below. You will need your Client ID and Client secret from Apsis One.'),
      '#open' => TRUE,
    ];

    $form['connection']['client_id'] = [
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_id'),
      '#size' => 50,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    $form['connection']['client_secret'] = [
      '#title' => $this->t('Client secret'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_secret'),
      '#size' => 50,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['tracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Tracking'),
      '#description' => $this->t('Description'),
      '#open' => TRUE,
    ];

    $form['tracking']['tracking_code'] = [
      '#title' => $this->t('Code'),
      '#type' => 'textarea',
      '#default_value' => $config->get('tracking_code'),
    ];

    $form['interface'] = [
      '#type' => 'details',
      '#title' => $this->t('Interface'),
      '#description' => $this->t('How the interface should look like.'),
      '#open' => TRUE,
    ];

    $form['interface']['view_mode'] = [
      '#title' => $this->t('Segmentation view mode'),
      '#type' => 'radios',
      '#default_value' => $config->get('view_mode'),
      '#options' => ['select' => 'Select list', 'checkboxes' => 'Checkboxes'],
      '#description' => $this->t('Decides how the segmentation selection should appear.'),
      '#size' => 50,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $cache_description = 'Cache configurations for APSIS One conditions checks. Cache is based on visitors APSIS One cookie via CacheContexts cookies:Ely_vID.
    Visibility conditions depends on logic in segments created in APSIS One, becuase of that pages content will not be updated until manually cache clear or
    max age on content.';

    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache'),
      '#description' => $this->t($cache_description),
      '#open' => TRUE,
    ];

    $maxage = $config->get('cache_max_age');
    if ($maxage == NULL) {
      $maxage = 3600;
    }

    $form['cache']['cache_max_age'] = [
      '#title' => $this->t('Max age'),
      '#type' => 'textfield',
      '#default_value' => $maxage,
      '#description' => $this->t('Max age for caches of conditions checks. Default 3600, 0 for no cache and -1 for permanent cache.'),
      '#required' => TRUE,
    ];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('page_cache')) {
      $form['cache']['page_cache'] = [
        '#markup' => '<p><strong>' . $this->t('Warning! "Internal Page Cache" module enabled. This will break caching for anonymous users, as it does not respect CookiesCacheContext!') . '</strong></p>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('apsisone.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('tracking_code', $form_state->getValue('tracking_code'))
      ->set('view_mode', $form_state->getValue('view_mode'))
      ->set('cache_max_age', $form_state->getValue('cache_max_age'))
      ->save();

    // Set values in variables.
    parent::submitForm($form, $form_state);

    // Refresh token after client id and client secret has been updated.
    $apsis = \Drupal::service('apsisone_service');
    if (!$apsis->refreshToken()) {
      $this->messenger()->addError('Failed to refresh token.');
    }
    else {
      $this->messenger->addStatus('New token fetched from ApsisOne.');
    }
  }

}
