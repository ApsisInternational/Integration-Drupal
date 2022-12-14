<?php

namespace Drupal\Tests\apsisone\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * @coversDefaultClass \Drupal\apsisone\Form\AdminSettingsForm
 * @group apsisone
 */
class AdminSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['apsisone'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer apsisone',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests filling in settings form.
   */
  public function testForm() {
    $edit = [
      'client_id' => '123',
      'client_secret' => '456',
      'view_mode' => 'select',
      'cache_max_age' => 3600,
      'tracking_code' => 'lorem',
    ];
    $this->drupalGet('admin/config/services/apsisone');
    $this->submitForm($edit, 'Save configuration');

    // Check that the config was saved.
    $expected_config = [
      'client_id' => '123',
      'client_secret' => '456',
      'view_mode' => 'select',
      'cache_max_age' => '3600',
      'tracking_code' => 'lorem',
    ];
    $actual_config = $this->container->get('config.factory')
      ->get('apsisone.settings');
    $this->assertEquals($expected_config, $actual_config->getRawData());

  }

}
