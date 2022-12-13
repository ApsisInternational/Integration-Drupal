<?php

namespace Drupal\apsisone\Plugin\Field\FieldFormatter\Condition;

use Drupal\apsisone\ApsisoneService;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\fico\Plugin\FieldFormatterConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description for your plugin.
 *
 * @FieldFormatterCondition(
 *   id = "apsisone_segments",
 *   label = @Translation("APSIS One segments"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class Segments extends FieldFormatterConditionBase implements ContainerFactoryPluginInterface {

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\apsisone\ApsisoneService
   */
  protected ApsisoneService $apsisone;

  /**
   * Constructs a FieldFormatterCondition plugin.
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
  public function alterForm(&$form, $settings) {
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
      '#default_value' => $settings['settings']['segments'],
      '#multiple' => TRUE,
    ];

    $form['match'] = [
      '#title' => 'APSIS One match',
      '#type' => 'select',
      '#options' => ['all' => 'All', 'any' => 'Any'],
      '#weight' => -5,
      '#default_value' => $settings['settings']['match'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    $build['#cache']['max-age'] = $this->apsisone->getMaxAge();
    $build['#cache']['contexts'][] = 'cookies:Ely_vID';

    $segments = $settings['settings']['segments'];
    $segments = array_map(function ($a) {
      return base64_decode($a);
    }, $segments);
    $build[$field]['#access'] = $this->apsisone->evaluateAgainstSegments($segments, $settings['settings']['match']);
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    return t("Condition: APSIS One segments");
  }

}
