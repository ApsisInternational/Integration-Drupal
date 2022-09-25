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

    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache'),
      '#description' => $this->t('Cache configurations'),
      '#open' => TRUE,
    ];

    $form['cache']['cache_enabled'] = [
      '#title' => $this->t('Use cache'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cache_enabled'),
      '#description' => $this->t('Use caching for nodes that has segmented content'),
    ];

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
      ->set('cache_enabled', $form_state->getValue('cache_enabled'))
      ->save();

    // Set values in variables.
    parent::submitForm($form, $form_state);

    // Refresh token after client id and client secret has been updated.
    $apsis = new ApsisoneService();
    $apsis->refreshToken();
  }

}
