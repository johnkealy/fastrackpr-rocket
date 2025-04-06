<?php

declare(strict_types=1);

namespace Drupal\Tests\simple_recaptcha_webform\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * JavaScripts tests for the Simple reCAPTCHA webform module.
 *
 * @group simple_recaptcha_webform
 */
class SimpleRecaptchaWebformJavascriptTestBase extends WebDriverTestBase {

  /**
   * WebAssert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $webAssert;

  /**
   * DocumentElement object.
   *
   * @var \Behat\Mink\Element\DocumentElement
   */
  protected $page;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'simple_recaptcha',
    'webform',
    'webform_ui',
    'simple_recaptcha_webform',
    'simple_recaptcha_webform_test',
    'file',
    'page_cache',
    'dynamic_page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * A simple user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  protected function getMinkDriverArgs() {
    // drupalCI chrome is executed via http://
    // for example: http://chromedriver-jenkins-drupal-contrib-652354:9515
    // due to this, we hit cross-origin errors when fetching ext. resources.
    $args = json_decode(parent::getMinkDriverArgs(), TRUE);
    $args[1]['chromeOptions']['args'][] = '--disable-web-security';
    return json_encode($args, JSON_UNESCAPED_SLASHES);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer simple_recaptcha',
      'access any webform configuration',
      'administer webform',
    ],
      'webadmin');
  }

  /**
   * Helper to configure the module.
   *
   * We need to set up reCAPTCHA test keys to make form alteration works.
   * Currently there's no way to set default config for testing.
   *
   * @param string $type
   *   Type to reCAPTCHA to use, v2 or v3.
   *
   * @see https://www.drupal.org/project/drupal/issues/913086
   *
   * @todo duplicate - move this logic to some sort of Trait.
   */
  public function configureModule($type = 'v2') {
    // cspell:disable
    $config = [
      'recaptcha_type' => $type,
      'site_key' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
      'secret_key' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
      'site_key_v3' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
      'secret_key_v3' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
      'form_ids' => 'user_login_form,user_pass,user_register_form',
      'v3_score' => 80,
    ];
    // cspell:enable
    \Drupal::configFactory()->getEditable('simple_recaptcha.config')
      ->setData($config)
      ->save();
  }

  /**
   * Verify that webform admin pages are accessible.
   */
  public function testHomepage() {
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Log in');
  }

}
