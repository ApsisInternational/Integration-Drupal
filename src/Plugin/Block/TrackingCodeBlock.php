<?php

namespace Drupal\apsisone\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an APSIS One tracking code block.
 *
 * @Block(
 *   id = "apsisone_tracking_code",
 *   admin_label = @Translation("Tracking code"),
 *   category = @Translation("APSIS One")
 * )
 */
class TrackingCodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs a new TrackingCodeBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManagerInterface $config_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->configManager->getConfigFactory()
      ->get('apsisone.settings');
    $tracking_code = $config->get('tracking_code');
    if (!empty($tracking_code)) {
      $tracking_code = preg_replace('#<script(.*?)>(.*?)</script>#is', '$2', $tracking_code);

      $build['#attached']['library'][] = 'apsisone/tracking';
      $build['#attached']['drupalSettings']['apsisone']['trackingcode'] = $tracking_code;
    }

    return $build;
  }

}
