<?php

namespace Drupal\apsisone;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Apsis One Config entity.
 */
interface ApsisoneConfigInterface extends ConfigEntityInterface {

  /**
   * Get type of Config.
   *
   * @return string
   *   Type.
   */
  public function getType();

  /**
   * Get subtype of Config.
   *
   * @return string
   *   Sub type.
   */
  public function getBundleType();

}
