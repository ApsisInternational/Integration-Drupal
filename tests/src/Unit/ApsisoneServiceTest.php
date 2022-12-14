<?php

namespace Drupal\Tests\apsisone\Unit;

use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\apsisone\ApsisoneService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * @covers \Drupal\apsisone\ApsisoneService
 * @group apsisone
 */
class ApsisoneServiceTest extends UnitTestCase {

  /**
   * Apsisone Service.
   *
   * @var \Drupal\apsisone\ApsisoneService
   */
  protected ApsisoneService $apsisoneService;

  /**
   * Mock handler for http client traffic.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected MockHandler $mockHandler;

  /**
   * State object for APSIS service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->mockHandler = new MockHandler([]);
    $handlerStack = HandlerStack::create($this->mockHandler);
    $client = new Client(['handler' => $handlerStack]);

    $configFactory = $this->getConfigFactoryStub([
      'apsisone.settings' => [
        'client_id' => '1234',
        'client_secret' => '4321',
        'cache_max_age' => '3600',
        'view_mode' => 'select',
      ],
    ]);

    $loggerFactory = $this->getMockBuilder('Psr\Log\LoggerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->state = $this->createMock(StateInterface::class);

    $this->apsisoneService = new ApsisoneService($client, $configFactory, $loggerFactory, $this->state);
    unset($_COOKIE['Ely_vID']);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::getApsisOneCookie
   * @covers \Drupal\apsisone\ApsisoneService::getApsisOneCmsCookie
   */
  public function testCookies() {
    $profile = $this->apsisoneService->getProfile();
    $this->assertEquals(FALSE, $profile);
    $_COOKIE['Ely_vID'] = '123';
    $profile = $this->apsisoneService->getProfile();
    $this->assertEquals('123', $profile);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::getMaxAge
   */
  public function testGetMaxAge() {
    $maxage = $this->apsisoneService->getMaxAge();
    $this->assertEquals(3600, $maxage);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::getViewMode
   */
  public function testGetViewMode() {
    $maxage = $this->apsisoneService->getViewMode();
    $this->assertEquals('select', $maxage);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::fetchToken
   * @covers \Drupal\apsisone\ApsisoneService::refreshToken
   */
  public function testToken() {
    $this->mockHandler->append(new Response(200, [], json_encode([])));
    $success = $this->apsisoneService->refreshToken();
    $this->assertEquals(FALSE, $success);

    $this->mockHandler->append(new Response(200, [], json_encode([
      'access_token' => '123',
      'expires_in' => '4560',
    ])));

    $this->state->expects($this->exactly(3))->method('set')->withConsecutive(
      ['apsisone_token_renewal', '0'],
      ['apsisone_token', '123'],
      ['apsisone_token_renewal', time() + 4560 - 3600],
    );
    $success = $this->apsisoneService->refreshToken();
    $this->assertEquals(TRUE, $success);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::getSegments
   */
  public function testSegments() {
    $this->state->expects($this->any())->method('get')
      ->will($this->returnValueMap([
        ['apsisone_token_renewal', 0, time() + 4560],
        ['apsisone_token', '', '123'],
      ]));
    $this->mockHandler->append(new Response(200, [], '
    {
      "items":[
      ]
    }'));
    $segments = $this->apsisoneService->getSegments();
    $this->assertEquals([], $segments);

    $this->mockHandler->append(new Response(200, [], '
    {
      "items":[
        {
          "discriminator": "key1",
          "name": "value1",
          "description": null,
           "versions":[]
        },
        {
          "discriminator": "key2",
          "name": "value2",
          "description": null,
           "versions":[]
        }
      ]
    }'));
    $segments = $this->apsisoneService->getSegments();
    $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $segments);

    $this->mockHandler->append(new Response(404, [], '
    {
      "items":[
        {
          "discriminator": "key1",
          "name": "value1",
          "description": null,
           "versions":[]
        },
      ]
    }'));
    $segments = $this->apsisoneService->getSegments();
    $this->assertEquals([], $segments);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::evaluateSegments
   */
  public function testEvaluateSegments() {
    $segments = $this->apsisoneService->evaluateSegments(['key1']);
    $this->assertEquals([], $segments);

    $_COOKIE['Ely_vID'] = '123';
    $this->state->expects($this->any())->method('get')
      ->will($this->returnValueMap([
        ['apsisone_token_renewal', 0, time() + 4560],
        ['apsisone_token', '', '123'],
      ]));
    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": true,
          "key2": false
        }
      }
    }
    '));

    $segments = $this->apsisoneService->evaluateSegments(['key1', 'key2']);
    $this->assertEquals([
      'success' => [
        'segments' => [
          'key1' => TRUE,
          'key2' => FALSE,
        ],
      ],
    ], $segments);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::evaluateSegmentsWithMatch
   */
  public function testEvaluateSegmentsWithMatch() {
    $_COOKIE['Ely_vID'] = '123';
    $_COOKIE['Ely_CMS_vID'] = '123';
    $this->state->expects($this->any())->method('get')
      ->will($this->returnValueMap([
        ['apsisone_token_renewal', 0, time() + 4560],
        ['apsisone_token', '', '123'],
      ]));

    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": true,
          "key2": true
        }
      }
    }
    '));
    $result = $this->apsisoneService->evaluateSegmentsWithMatch([
      'key1',
      'key2',
    ]);
    $this->assertEquals(TRUE, $result);

    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": true,
          "key2": false
        }
      }
    }
    '));
    $result = $this->apsisoneService->evaluateSegmentsWithMatch([
      'key1',
      'key2',
    ]);
    $this->assertEquals(FALSE, $result);

    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": true,
          "key2": false
        }
      }
    }
    '));
    $result = $this->apsisoneService->evaluateSegmentsWithMatch([
      'key1',
      'key2',
    ], 'any');
    $this->assertEquals(TRUE, $result);

    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": false,
          "key2": false
        }
      }
    }
    '));
    $result = $this->apsisoneService->evaluateSegmentsWithMatch([
      'key1',
      'key2',
    ], 'any');
    $this->assertEquals(FALSE, $result);
  }

  /**
   * @covers \Drupal\apsisone\ApsisoneService::mergeProfiles
   */
  public function testMergeProfiles() {
    $_COOKIE['Ely_vID'] = '123';
    $_COOKIE['Ely_CMS_vID'] = '1234';
    $this->state->expects($this->any())->method('get')
      ->will($this->returnValueMap([
        ['apsisone_token_renewal', 0, time() + 4560],
        ['apsisone_token', '', '123'],
      ]));

    $this->mockHandler->append(new Response(200, [], ''));

    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": true
        }
      }
    }
    '));
    $this->apsisoneService->evaluateSegmentsWithMatch(['key1']);

    $this->mockHandler->append(new Response(400, [], 'error'));
    $this->mockHandler->append(new Response(200, [], '
    {
      "matches":{
        "segments":{
          "key1": true
        }
      }
    }
    '));
    $this->apsisoneService->evaluateSegmentsWithMatch(['key1']);

  }

}
