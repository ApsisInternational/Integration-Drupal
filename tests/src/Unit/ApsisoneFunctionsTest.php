<?php

namespace Drupal\Tests\apsisone\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\apsisone\Entity\ApsisoneConfig;
use Drupal\apsisone\Entity\Segmentation;
use Drupal\apsisone\ApsisoneService;
use Drupal\apsisone\ApsisoneFunctions;
use GuzzleHttp\Psr7\Response;

/**
 * @group apsisone
 */
class ApsisoneFunctionsTest extends UnitTestCase {

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
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

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

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->method('getKeys')->willReturn([]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getDefinition')->willReturn($entityType);

    $entityTypeRepository = $this->createMock(EntityTypeRepository::class);
    $container->set('entity_type.repository', $entityTypeRepository);

    $this->storage = $this->createMock(EntityStorageInterface::class);
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->storage);
    $container->set('entity_type.manager', $entityTypeManager);

    $createdFieldDefinition = $this->createMock(FieldDefinitionInterface::class);

    $createdFieldItemList = $this->createMock(FieldItemListInterface::class);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->method('getDefaultStorageSettings')
      ->willReturn([]);
    $fieldTypePluginManager->method('getDefaultFieldSettings')->willReturn([]);
    $fieldTypePluginManager->method('createFieldItemList')
      ->willReturn($createdFieldItemList);
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager);

    $entityFieldManager = $this->createMock(EntityFieldManager::class);
    $entityFieldManager->method('getFieldDefinitions')->willReturn([
      'created' => $createdFieldDefinition,
    ]);
    $container->set('entity_field.manager', $entityFieldManager);

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

    $container->set('state', $this->state);
  }

  public function testGetConfigEntities() {
    $query = $this->createMock(QueryInterface::class);
    $query->method('sort')->willReturn($query);
    $query->method('execute')->willReturn([1]);

    $config = new ApsisoneConfig([
      'type' => 'field_config',
      'bundle_type' => 'field_example_1',
    ], 'apsisone_config');

    $config2 = new ApsisoneConfig([
      'type' => 'field_config',
      'bundle_type' => 'field_example_2',
    ], 'apsisone_config');

    $config3 = new ApsisoneConfig([
      'type' => 'block',
      'bundle_type' => 'block_example_3',
    ], 'apsisone_config');

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn([
      $config,
      $config2,
      $config3,
    ]);

    $this->assertEquals([
      'field_example_1',
      'field_example_2',
    ], ApsisoneFunctions::getConfigEntities('field_config'));
  }

  public function testGetExistingSegments() {
    $query = $this->createMock(QueryInterface::class);
    $query->method('sort')->willReturn($query);
    $query->method('execute')->willReturn([1]);

    $segments = [
      'usercreated.segments.asdf' => 'usercreated.segments.asdf',
      'usercreated.segments.qwer' => 'usercreated.segments.qwer',
      'usercreated.segments.zxcv' => 'usercreated.segments.zxcv',
    ];

    $values = [
      'entity_id' => 1,
      'segment' => serialize($segments),
      'entity_type' => 'node',
      'type' => 'field',
      'segmented_field' => 'field_example_3',
      'segmented_field_match' => 'any',
    ];

    $segmentation = new Segmentation([], 'segmentation');
    $segmentation->entity_id = (object) ['value' => $values['entity_id']];
    $segmentation->segment = (object) ['value' => $values['segment']];
    $segmentation->entity_type = (object) ['value' => $values['entity_type']];
    $segmentation->type = (object) ['value' => $values['type']];
    $segmentation->segmented_field = (object) ['value' => $values['segmented_field']];
    $segmentation->segmented_field_match = (object) ['value' => $values['segmented_field_match']];

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadByProperties')->willReturn([$segmentation]);

    $existing_segments = ApsisoneFunctions::getExistingSegments($values['entity_id'], $values['entity_type'], $values['type']);

    $this->assertEquals([
      'existing' => $segments,
      'field_match' => $values['segmented_field_match'],
    ], $existing_segments);
  }

  public function testProfileBelongsToSegment() {
    $segments = [
      'usercreated.segments.asdf' => 'usercreated.segments.asdf',
      'usercreated.segments.qwer' => 'usercreated.segments.qwer',
      'usercreated.segments.zxcv' => 'usercreated.segments.zxcv',
    ];

    $existing_segmentation = new Segmentation([], 'segmentation');
    $existing_segmentation->segment = (object) ['value' => serialize($segments)];
    $existing_segmentation->segmented_field_match = (object) ['value' => 'any'];

    // Preparing segments - not belonging to segments
    $segments_response_false = [
      'usercreated.segments.asdf' => FALSE,
      'usercreated.segments.qwer' => FALSE,
      'usercreated.segments.zxcv' => FALSE,
    ];

    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'access_token' => 'asdf',
        'expires_in' => 'qwer',
      ])),
    );
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'matches' => ['segments' => $segments_response_false],
      ])),
    );

    $this->assertEquals(FALSE, ApsisoneFunctions::profileBelongsToSegment($existing_segmentation, $this->apsisoneService, 'asdf'));

    // Preparing segments - belonging to 1 segment
    $segments_response_true = [
      'usercreated.segments.asdf' => FALSE,
      'usercreated.segments.qwer' => TRUE,
      'usercreated.segments.zxcv' => FALSE,
    ];
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'access_token' => 'asdf',
        'expires_in' => 'qwer',
      ])),
    );
    $this->mockHandler->append(
      new Response(200, [], json_encode([
        'matches' => ['segments' => $segments_response_true],
      ])),
    );

    $this->assertEquals(TRUE, ApsisoneFunctions::profileBelongsToSegment($existing_segmentation, $this->apsisoneService, 'asdf'));
  }

}
