<?php

declare(strict_types=1);

namespace Drupal\Tests\simple_recaptcha\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test base for simple_recaptcha module.
 *
 * @group simple_recaptcha
 */
class SimpleRecaptchaTestBase extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['simple_recaptcha'];

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'claro';

  /**
   * A simple user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer simple_recaptcha',
    ],
      'webadmin');
  }

  /**
   * Tests that the configuration page can be reached.
   */
  public function testHomepage() {
    // Permissions / config page existence check.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
