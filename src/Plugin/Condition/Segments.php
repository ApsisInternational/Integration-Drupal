<?php

namespace Drupal\apsisone\Plugin\Condition;

use Drupal\apsisone\ApsisoneService;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'APSIS One' segments condition.
 *
 * @Condition(
 *   id = "apsisone_segments",
 *   label = @Translation("APSIS One"),
 * )
 */
class Segments extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\apsisone\ApsisoneService
   */
  protected $apsisone;

  /**
   * Constructs a condition plugin.
   *
   * @param \Drupal\apsisone\ApsisoneService $apsisone
   *   APSIS One service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ApsisoneService $apsisone, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->apsisone = $apsisone;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('apsisone_service'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_values = [
      'segments' => [],
      'match' => 'all',
    ];
    return $default_values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $apsis = \Drupal::service('apsisone_service');
    $segments = $apsis->getSegments();
    $segmentsbase = [];
    foreach ($segments as $key => $value) {
      $segmentsbase[base64_encode($key)] = $value;
    }
    $view_mode = $apsis->getViewMode();

    $type = 'checkboxes';
    if ($view_mode == 'select') {
      $type = 'select';
    }

    // Add them to the form.
    $form['segments'] = [
      '#title' => 'APSIS One segmentations',
      '#type' => $type,
      '#options' => $segmentsbase,
      '#weight' => -10,
      '#default_value' => $this->configuration['segments'],
      '#multiple' => TRUE,
    ];

    $form['match'] = [
      '#title' => 'APSIS One match',
      '#type' => 'select',
      '#options' => ['all' => 'All', 'any' => 'Any'],
      '#weight' => -5,
      '#default_value' => $this->configuration['match'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['segments'] = $form_state->getValue('segments');
    $this->configuration['match'] = $form_state->getValue('match');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('APSIS One summary');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $segments = $this->configuration['segments'];
    $segments = array_map(function ($a) {
      return base64_decode($a);
    }, $segments);
    return $this->apsisone->evaluateSegmentsWithMatch(array_values($segments), $this->configuration['match']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'cookies:Ely_vID';
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $segments = $this->configuration['segments'];
    $tags = ['apsisone:' . $this->configuration['match'] . ':' . implode('-', $segments)];
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->apsisone->getMaxAge();
  }

}
