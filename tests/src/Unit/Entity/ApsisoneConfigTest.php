<?php

namespace Drupal\Tests\apsisone\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\apsisone\Entity\ApsisoneConfig;

/**
 * @group apsisone
 */
class ApsisoneConfigTest extends UnitTestCase {
  public function testConstruction()
  {
      $config = new ApsisoneConfig([
          'type' => 'asdf',
          'bundle_type' => 'qwer',
      ], 'apsisone_config');

      $this->assertEquals('asdf', $config->getType());
      $this->assertEquals('qwer', $config->getBundleType());
  }
}
