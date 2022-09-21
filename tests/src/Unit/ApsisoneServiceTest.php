<?php

namespace Drupal\Tests\apsisone\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\apsisone\ApsisoneService;
use GuzzleHttp\Psr7\Response;

/**
 * @group apsisone
 */
class ApsisoneServiceTest extends UnitTestCase {

  protected $container;

  /**
   * @var \Drupal\apsisone\ApsisoneService
   */
  protected $apsisoneService;

  /**
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $mockHandler;

  /**
   * \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->mockHandler = new \GuzzleHttp\Handler\MockHandler();
    $handlerStack = \GuzzleHttp\HandlerStack::create($this->mockHandler);
    $httpClient = new \GuzzleHttp\Client(['handler' => $handlerStack]);

    $configFactory = $this->getConfigFactoryStub([
      'apsisone.settings' => [
        'client_id' => '1234',
        'client_secret' => '4321',
      ],
    ]);
    $config = $configFactory->get('apsisone.settings');
    // @todo Replace with container mock?

    $this->apsisoneService = ApsisoneService::construct($httpClient, $config);

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $states = [
      'apsisone_token' => NULL,
      'apsisone_token_renewal' => 0,
    ];

    $this->state = $this->createMock(StateInterface::class);
    $this->state->method('get')->willReturnCallback(
      function ($key, $default = NULL) use (&$states) {
        return $states[$key] ?? $default;
      }
    );
    $this->state->method('set')->willReturnCallback(
      function ($key, $value) use (&$states) {
        $states[$key] = $value;
      }
    );
    $this->container->set('state', $this->state);
  }

  public function testGetCookie() {
    $cookie = $this->apsisoneService->getApsisOneCookie();
    $this->assertEquals(FALSE, $cookie);
  }

  public function testGetToken() {
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'access_token' => 'asdf',
        'expires_in' => 'qwer',
      ])),
    );

    $token = $this->apsisoneService->getToken();

    $this->assertEquals('asdf', $token);
  }

  public function testSetToken() {
    $this->apsisoneService->setToken('asdf', 60 * 60 * 24);
    $this->assertEquals('asdf', $this->state->get('apsisone_token'));
  }

  public function testListSegments() {
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'access_token' => 'asdf',
        'expires_in' => 'qwer',
      ])),
    );
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'items' => ['asdf'],
      ])),
    );

    $segments = $this->apsisoneService->listSegments();

    $this->assertEquals(['success' => ['asdf']], $segments);
  }

  public function testEvaluateProfile() {
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'access_token' => 'asdf',
        'expires_in' => 'qwer',
      ])),
    );
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'matches' => ['asdf'],
      ])),
    );

    $profile = $this->apsisoneService->evaluateProfile(['asdf'], 'qwer');

    $this->assertEquals(['success' => ['asdf']], $profile);
  }

  public function testGetApsisOneCookie() {
    $this->assertEquals(FALSE, $this->apsisoneService->getApsisOneCookie());
    $_COOKIE['Ely_vID'] = 'asdf';
    $this->assertEquals('asdf', $this->apsisoneService->getApsisOneCookie());
  }

}
