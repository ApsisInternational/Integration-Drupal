<?php

namespace Drupal\Tests\apsisone\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\apsisone\SegmentationListBuilder;

/**
 * @group apsisone
 */
class SegmentationListBuilderTest extends UnitTestCase {

  /**
   * @var \Drupal\apsisone\SegmentationListBuilder
   */
  protected $builder;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('id')->willReturn('asdf');

    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->builder = new SegmentationListBuilder($entity_type, $this->storage);

    $this->builder->setStringTranslation($this->getStringTranslationStub());
  }

  public function testRender() {
    $query = $this->createMock(QueryInterface::class);
    $query->method('sort')->willReturn($query);
    $query->method('accessCheck')->willReturn($query);
    $query->method('execute')->willReturn([1]);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn(1);
    $entity->entity_id = (object) ['value' => 1];
    $entity->segment = (object) ['value' => 'asdf'];
    $entity->created = (object) ['value' => date('Ymd')];

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn([$entity]);

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('invokeAll')->willReturn([]);
    $moduleHandler->method('alter');
    $this->builder->setModuleHandler($moduleHandler);

    $build = $this->builder->render();
    $this->assertFalse(empty($build['description']['#markup']));
  }

}
