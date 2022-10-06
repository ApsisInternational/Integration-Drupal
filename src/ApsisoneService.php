<?php

namespace Drupal\apsisone;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ImmutableConfig;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Apsis One Service.
 */
class ApsisoneService {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->httpClient = new Client(['base_uri' => 'https://api.apsis.one']);
  }

  /**
   * Static constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Http client.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config.
   *
   * @return ApsisoneService
   *   ApisonsService object.
   *
   * @todo Fix with a new service.
   */
  public static function construct(ClientInterface $httpClient, ImmutableConfig $config) {
    $service = new static();
    $service->httpClient = $httpClient;
    $service->config = $config;

    return $service;
  }

  /**
   * Get config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Config.
   */
  private function config() {
    if (!empty($this->config)) {
      $this->config = \Drupal::config('apsisone.settings');
    }

    return $this->config;
  }

  /**
   * Request the access token.
   */
  public function requestToken() {
    $client = $this->httpClient;
    $config = $this->config();

    $client_id = $config->get('client_id');
    $client_secret = $config->get('client_secret');

    $payload = [
      'grant_type' => 'client_credentials',
      'client_id' => $client_id,
      'client_secret' => $client_secret,
    ];
    try {
      $response = $client->post('/oauth/token', ['form_params' => $payload]);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $response_body = $response->getBody()->getContents();
      return ['error_message' => $response_body];
    }
    $parsed_response = Json::decode((string) $response->getBody());

    return [
      'access_token' => $parsed_response['access_token'],
      'expires_in' => $parsed_response['expires_in'],
    ];
  }

  /**
   * Get token.
   *
   * @return string
   *   Token.
   */
  public function getToken() {
    if (!$this->isTokenValid()) {
      $this->refreshToken();
    }

    $token = \Drupal::state()->get('apsisone_token');

    return $token;
  }

  /**
   * Set token.
   *
   * @param string $token
   *   Token to set.
   * @param int $expire
   *   Expire time.
   */
  public function setToken(string $token, int $expire) {
    // Set token renewaltime to 1 hour before it actually expires.
    $force_token_early_expire = 3600;
    $token_renewal = time() + $expire - $force_token_early_expire;

    // Save token and renewal time.
    \Drupal::state()->set('apsisone_token', $token);
    \Drupal::state()->set('apsisone_token_renewal', $token_renewal);
  }

  /**
   * Refresh token.
   */
  public function refreshToken() {
    // Get token.
    $response = $this->requestToken();

    // Set token.
    if (isset($response['access_token'])) {
      $this->setToken($response['access_token'], intval($response['expires_in']));
    }
    else {
      \Drupal::messenger()->addMessage('Failed to refresh token', 'error');
      \Drupal::logger('apsisone')->error($response['error_message']);
    }
  }

  /**
   * Token valdation.
   *
   * @return bool
   *   If token is still valid.
   */
  public function isTokenValid() {
    $token_renewal = \Drupal::state()->get('apsisone_token_renewal');

    if (time() < $token_renewal) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * List segments.
   *
   * @return array
   *   Segments.
   */
  public function listSegments() {
    $client = $this->httpClient;
    try {
      $token = $this->getToken();

      $response = $client->get('/audience/segments', [
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
      ]);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $response_body = $response->getBody()->getContents();
      return ['error_message' => $response_body];
    }
    // Get the actual access token.
    $parsed_response = Json::decode((string) $response->getBody());
    $segments = $parsed_response['items'];

    return ['success' => $segments];
  }

  /**
   * Get segments.
   *
   * @return array
   *   Segments.
   */
  public function getSegments() {
    $segments = $this->listSegments();
    $segments_options = [];

    // Prepare and show the selectable segment values.
    if (isset($segments['success'])) {

      foreach ($segments['success'] as $segment) {
        $segments_options[$segment['discriminator']] = $segment['name'];
      }
    }

    return $segments_options;
  }

  /**
   * Evaluate Profile.
   *
   * @param array $segments
   *   Segments.
   * @param string $profile
   *   Profile.
   *
   * @return array
   *   Segments successful.
   */
  public function evaluateProfile(array $segments, string $profile) {
    $token = $this->getToken();
    $client = $this->httpClient;

    $payload = [
      'segments' => [],
      'time_zone' => 'Europe/Stockholm',
    ];

    foreach ($segments as $segment) {
      $payload['segments'][] = ['discriminator' => $segment];
    }

    $keyspace_discriminator = 'com.apsis1.keyspaces.integrations.global.cms';

    try {
      $response = $client->post('/audience/keyspaces/' . $keyspace_discriminator . '/profiles/' . $profile . '/evaluations', [
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
        'form_params' => $payload,
      ]);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $response_body = $response->getBody()->getContents();
      return ['error_message' => $response_body];
    }
    // Get the actual response.
    $parsed_response = Json::decode((string) $response->getBody());
    $segments = $parsed_response['matches'];

    return ['success' => $segments];
  }

  /**
   * Merge Profiles.
   *
   * @param string $profile
   *   Profile.
   *
   * @return bool|string|string[]
   *   Return value.
   *
   * @todo Add better descriptions. And better return type.
   */
  public function mergeProfiles(string $profile) {
    $token = $this->getToken();
    $client = $this->httpClient;

    $keyspace_discriminator = 'com.apsis1.keyspaces.web';
    $keyspace_discriminator_cms = 'com.apsis1.keyspaces.integrations.global.cms';

    $payload = [
      'profiles' => [
        [
          'keyspace_discriminator' => $keyspace_discriminator,
          'profile_key' => $profile,
        ],
        [
          'keyspace_discriminator' => $keyspace_discriminator_cms,
          'profile_key' => $profile,
        ],
      ],
      'time_zone' => 'Europe/Stockholm',
    ];

    try {
      $response = $client->put('/audience/profiles/merges', [
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => 'Bearer ' . $token,
        ],
        'form_params' => $payload,
      ]);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $response_body = $response->getBody()->getContents();

      if ($response->getStatusCode() == 409) {
        $response_body = '409 profile merge conflict. Profile: ' . $profile . ' Response: ' . $response_body;
      }

      $status_codes = [409, 204, 410];
      if (in_array($response->getStatusCode(), $status_codes)) {
        setcookie("Ely_CMS_vID", $profile, time() + (10 * 365 * 24 * 60 * 60), '/');
        return 'cookieset';
      }
      \Drupal::logger('apsisone')->error($response_body);
      return ['error_message' => $response_body];
    }

    $status_codes = [409, 204, 410];
    if (in_array($response->getStatusCode(), $status_codes)) {
      setcookie("Ely_CMS_vID", $profile, time() + (10 * 365 * 24 * 60 * 60), '/');
      return 'cookieset';
    }
    return TRUE;
  }

  /**
   * Get user id from cookie.
   */
  public function getApsisOneCookie() {
    $cookie = @$_COOKIE['Ely_vID'];

    if (!empty($cookie)) {
      return $cookie;
    }
    return FALSE;
  }

  /**
   * Get user id from CMS cookie.
   */
  public function getApsisOneCmsCookie() {
    $cookie = @$_COOKIE['Ely_CMS_vID'];

    if (!empty($cookie)) {
      return $cookie;
    }
    return FALSE;
  }

}
