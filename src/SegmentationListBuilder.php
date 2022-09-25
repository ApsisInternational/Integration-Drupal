<?php

namespace Drupal\apsisone;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for segmentation entity.
 *
 * @ingroup segmentation
 */
class SegmentationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('List of all Apsis One segmented content.'),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Segmentation list.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Segmentation ID');
    $header['entity_id'] = $this->t('Entity ID');
    $header['segment'] = $this->t('Segment');
    $header['created'] = $this->t('Created');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\apsisone\Entity\Segmentation $entity */
    $row['id'] = $entity->id();
    $row['entity_id'] = $entity->entity_id->value;
    $row['segment'] = $entity->segment->value;
    $row['created'] = $entity->created->value;
    return $row + parent::buildRow($entity);
  }

}
