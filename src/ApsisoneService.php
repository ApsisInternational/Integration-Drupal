<?php

namespace Drupal\apsisone;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

/**
 * APSIS One Service.
 */
class ApsisoneService {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * Class constructor.
   */
  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $config_factory, LoggerInterface $logger, StateInterface $state) {
    $this->httpClient = $httpClient;
    $this->config = $config_factory->get('apsisone.settings');
    $this->logger = $logger;
    $this->state = $state;
  }

  /**
   * Makes request to APSIS One.
   *
   * Fetching valid token before request.
   *
   * @param string $method
   *   Method for call.
   * @param string $url
   *   Url to endpoint, excluding domain.
   * @param array $payload
   *   Payload array for post requests.
   *
   * @return array
   *   Response data.
   */
  protected function request(string $method, string $url, array $payload = []): array {
    $token = $this->fetchToken();
    $headers = [
      'Accept' => 'application/json',
      'Authorization' => 'Bearer ' . $token,
    ];
    return $this->rawRequest($method, $url, $headers, $payload);
  }

  /**
   * Get APSIS One protocol and domain for API call.
   *
   * @return string
   *   Base url.
   */
  private function getBaseUrl() {
    // @todo Make a config.
    return 'https://api.apsis.one';
  }

  /**
   * Makes raw request to APIS One endpoint.
   *
   * @param string $method
   *   Method for call.
   * @param string $url
   *   Url to endpoint, excluding domain.
   * @param array $headers
   *   Array of headers to send.
   * @param array $payload
   *   Payload array for post requests.
   *
   * @return array
   *   Response data.
   */
  protected function rawRequest(string $method, string $url, array $headers = [], array $payload = []): array {
    $client = $this->httpClient;
    $data = [
      'headers' => $headers,
      'form_params' => $payload,
    ];

    $response = '';
    try {
      if ($method == 'post') {
        $response = $client->post($this->getBaseUrl() . $url, $data);
      }
      if ($method == 'get') {
        $response = $client->get($this->getBaseUrl() . $url, $data);
      }
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $response_body = $response->getBody()->getContents();
      $this->logger->warning('ClientException in APSIS One request: %url => %message', [
        '%url' => $url,
        '%message' => $response_body,
      ]);
      return ['error_message' => $response_body];
    }
    return Json::decode((string) $response->getBody());
  }

  /**
   * Fetch new token from APSIS One if needed.
   *
   * @return string
   *   Token to use. Empty string if not successfully.
   */
  protected function fetchToken(): string {
    // Return token if cached and valied. Otherwise, refresh or create.
    $token_renewal = $this->state->get('apsisone_token_renewal', 0);
    if (time() < $token_renewal) {
      return $this->state->get('apsisone_token', '');
    }
    $client_id = $this->config->get('client_id');
    $client_secret = $this->config->get('client_secret');
    $payload = [
      'grant_type' => 'client_credentials',
      'client_id' => $client_id,
      'client_secret' => $client_secret,
    ];
    $response = $this->rawRequest('post', '/oauth/token', [], $payload);
    if (empty($response['access_token'])) {
      $this->logger->error('Could not fetch token!');
      return '';
    }

    // Set token renewaltime to 1 hour before it actually expires.
    $token_renewal = time() + $response['expires_in'] - 3600;

    // Save token and renewal time.
    $this->state->set('apsisone_token', $response['access_token']);
    $this->state->set('apsisone_token_renewal', $token_renewal);
    return $response['access_token'];
  }

  /**
   * Refreshes token.
   *
   * @return bool
   *   If refresh of token was successfully.
   */
  public function refreshToken(): bool {
    $this->state->delete('apsisone_token');
    $this->state->set('apsisone_token_renewal', 0);
    $token = $this->fetchToken();
    return !empty($token);
  }

  /**
   * Get segments.
   *
   * @return array
   *   Segments.
   */
  public function getSegments(): array {
    $segments = [];
    $data = $this->request('get', '/audience/segments');
    if (!empty($data['items'])) {
      foreach ($data['items'] as $segment) {
        $segments[$segment['discriminator']] = $segment['name'];
      }
    }
    return $segments;
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
  public function evaluateProfile(array $segments, string $profile): array {
    if (empty($profile)) {
      return [];
    }
    $payload = [
      'segments' => [],
      'time_zone' => 'Europe/Stockholm',
    ];

    foreach ($segments as $segment) {
      $payload['segments'][] = ['discriminator' => $segment];
    }

    $keyspace_discriminator = 'com.apsis1.keyspaces.integrations.global.cms';
    $response = $this->request('post', '/audience/keyspaces/' . $keyspace_discriminator . '/profiles/' . $profile . '/evaluations', $payload);
    if (empty($response['matches'])) {
      return $response;
    }

    return ['success' => $response['matches']];
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
    $token = $this->fetchToken();

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
      $response = $this->httpClient->put($this->getBaseUrl() . '/audience/profiles/merges', [
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
      $this->logger->error($response_body);
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
    return !empty($cookie) ? $cookie : FALSE;
  }

  /**
   * Get user id from CMS cookie.
   */
  public function getApsisOneCmsCookie() {
    $cookie = @$_COOKIE['Ely_CMS_vID'];
    return !empty($cookie) ? $cookie : FALSE;
  }

  /**
   * Merge profiles if its needed.
   */
  protected function mergeProfilesIfNeeded() {
    // Get profile.
    $profile = $this->getApsisOneCookie();

    // Get CMS profile.
    $profile_cms = $this->getApsisOneCmsCookie();

    if (!empty($profile) && $profile !== $profile_cms) {
      $this->mergeProfiles($profile);
    }
  }

  /**
   * Check current visitors against segments.
   *
   * @param array $segments
   *   List of segments.
   * @param string $match
   *   Match type.
   *
   * @return bool
   *   If visitors matches the segments or not.
   */
  public function evaluateAgainstSegments(array $segments, string $match = 'all') {
    $this->mergeProfilesIfNeeded();
    $profile = $this->getApsisOneCookie();
    $evaluate = $this->evaluateProfile($segments, $profile);

    if (isset($evaluate["success"]["segments"])) {
      // One segments returned true.
      if ($match == 'any' && in_array(TRUE, $evaluate["success"]["segments"])) {
        return TRUE;
      }
      // All segments returned true.
      if ($match == 'all' && !in_array(FALSE, $evaluate["success"]["segments"])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get view mode of segments in admin interface.
   *
   * @return string
   *   View mode for segments. select / checkboxes.
   */
  public function getViewMode(): string {
    $view_mode = $this->config->get('view_mode');
    return ($view_mode !== NULL) ? $view_mode : 'select';
  }

  /**
   * Get max age settings for APSIS One data.
   *
   * @return int
   *   Max age for caching.
   */
  public function getMaxAge(): int {
    $max_age = $this->config->get('cache_max_age');
    return ($max_age !== NULL) ? (int) $max_age : 3600;
  }

}
