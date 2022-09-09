<?php

namespace Drupal\apsisone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Administration page callbacks for the Apsis One module.
 */

/**
 * Class AdminSettingsForm.
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

    $form['apsisone_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Apsis One settings'),
      '#description' => $this->t('Fill in the form below. You will need your Client ID and Client secret from Apsis One.'),
      '#open' => TRUE,
    );

    $form['apsisone_settings']['client_id'] = array(
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_id'),
      '#size' => 50,
      '#maxlength' => 50,
      '#required' => TRUE,
    );

    $form['apsisone_settings']['client_secret'] = array(
      '#title' => $this->t('Client secret'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_secret'),
      '#size' => 50,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['apsisone_settings']['view_mode'] = array(
      '#title' => $this->t('Apsis One Segmentation view mode'),
      '#type' => 'radios',
      '#default_value' => $config->get('view_mode'),
      '#options' => ['select' => 'Select list', 'checkboxes' => 'Checkboxes'],
      '#description' => $this->t('Decides how the segmentation selection should appear.'),
      '#size' => 50,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['apsisone_settings']['cache_enabled'] = array(
      '#title' => $this->t('Use cache'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cache_enabled'),
      '#description' => $this->t('Use caching for nodes that has segmented content'),
      '#suffix' => '<hr /><p><a href="/admin/config/services/apsisone/apsisone" class="button">GOTO Apsis One config</a></p>',
    );
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('apsisone.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('view_mode', $form_state->getValue('view_mode'))
      ->set('cache_enabled', $form_state->getValue('cache_enabled'))
      ->save();

    // Set values in variables.
    parent::submitForm($form, $form_state);

    // Refresh token after client id and client secret has been updated
    $apsis = new \Drupal\apsisone\ApsisoneService;
    $apsis->refreshToken();
  }
}
