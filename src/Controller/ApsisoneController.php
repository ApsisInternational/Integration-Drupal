<?php

namespace Drupal\apsisone\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\apsisone\ApsisoneService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ApsisoneController for different debug outputs.
 *
 * @package Drupal\apsisone\Controller
 */
class ApsisoneController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * ApsisoneController Class Service.
   *
   * @var \Drupal\apsisone\ApsisoneService
   */
  protected $apsisoneService;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ApsisoneService $apsisoneService) {
    $this->config = $config_factory->getEditable('apsisone.settings');
    $this->apsisoneService = $apsisoneService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('apsisone_service')
    );
  }

  /**
   * Lists Apsis One segments.
   */
  public function evaluate() {
    $token = \Drupal::state()->get('apsisone_token');
    $profile = \Drupal::request()->get('profile_id');
    $segment = \Drupal::request()->get('segment');

    if (empty($profile)) {
      \Drupal::messenger()->addMessage('Missing profile ID.', 'error');
      return $this->redirect('<front>');
    }

    $response = $this->apsisoneService->evaluateProfile($segment, $profile);

    if (isset($response['success'])) {
      $output = '<ul>';
      foreach ($response['success'] as $items) {
        $output .= '<li>' . json_encode($items) . '</li>';
      }
      $output .= '</ul>';

      return ['#markup' => $output];
    }
    else {
      \Drupal::messenger()
        ->addMessage('Failed connecting to Apsis One.', 'error');
      \Drupal::logger('apsisone')->error($response['error_message']);
      return $this->redirect('<front>');
    }
    return FALSE;
  }

  /**
   * Show Apsis One cookie user id.
   */
  public function cookie() {

    $cookie = $this->apsisoneService->getApsisOneCookie();
    if ($cookie) {
      $output = '<h2>' . $cookie . '</h2>';
      return ['#markup' => $output];
    }
    else {
      \Drupal::messenger()->addMessage('Failed to get cookie', 'error');
      return FALSE;
    }
  }

}
