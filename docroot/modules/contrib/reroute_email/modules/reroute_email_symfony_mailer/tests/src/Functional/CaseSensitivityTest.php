<?php

namespace Drupal\Tests\reroute_email_symfony_mailer\Functional;

use Drupal\Tests\reroute_email\Functional\CaseSensitivityTest as MainCaseSensitivityTest;

/**
 * Test Reroute Email with multiple recipients.
 *
 * @group reroute_email_symfony_mailer
 */
class CaseSensitivityTest extends MainCaseSensitivityTest {

  /**
   * {@inheritdoc}
   */
  protected string $reroutePluginId = 'reroute_email_symfony_mailer';


  /**
   * {@inheritdoc}
   */
  protected string $mailCollectorState = 'mailer_test.emails';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'reroute_email_symfony_mailer',

    // Set email transport DSN to 'null://default'.
    'symfony_mailer_test',
  ];

}
