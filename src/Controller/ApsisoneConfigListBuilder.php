<?php

namespace Drupal\apsisone\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Apsis One Config.
 */
class ApsisoneConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Apsis One Config');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->getBundleType();
    $row['type'] = $entity->getType();

    return $row + parent::buildRow($entity);
  }

}
