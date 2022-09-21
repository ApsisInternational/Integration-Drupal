<?php

namespace Drupal\Tests\apsisone\Unit\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\apsisone\Entity\Segmentation;

/**
 * @group apsisone
 */
class SegmentationTest extends UnitTestCase {

  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->method('getKeys')->willReturn([]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getDefinition')->willReturn($entityType);
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
  }

  public function testConstruction() {
    $segmentation = new Segmentation([], 'segmentation');
    $this->assertNull($segmentation->getCreatedTime());
  }

  public function testBaseFieldDefinitions() {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $fields = Segmentation::baseFieldDefinitions($entity_type);
    $this->assertNotNull($fields);
  }

}

namespace Drupal\apsisone\Entity;

use Drupal\Core\StringTranslation\TranslatableMarkup;

function t($string, array $args = [], array $options = []) {
  return new TranslatableMarkup($string, $args, $options);
}
