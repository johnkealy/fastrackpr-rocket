<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;

/**
 * Test that attachments are included when redirecting email.
 *
 * @group reroute_email
 */
class AttachmentTest extends RerouteEmailBrowserTestBase {

  /**
   * Test attachments to be present on rerouted mail.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAttachmentsArePresentOnReroutedMail() {
    // Configure to reroute to rerouteDestination.
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => TRUE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => static::$rerouteDestination,
    ]);

    // Generate a new email.
    /** @var \Drupal\Core\Mail\MailManager $mailManager */
    $mailManager = \Drupal::service("plugin.manager.mail");
    $params["context"]["subject"] = "Test mail subject.";
    $params["context"]["message"] = "Test mail message.";

    // Attach a fake file.
    $file = new \stdClass();
    $file->uri = 'public://sample.pdf';
    $file->filename = 'sample.pdf';
    $file->filemime = 'application/pdf';
    $params['attachments'][] = $file;

    // Send mail.
    $mailManager->mail("system", "mail", static::$originalDestination, 'en', $params);
    $this->assertMail('to', static::$rerouteDestination, new FormattableMarkup('Email was rerouted to @address.', ['@address' => static::$rerouteDestination]));

    // Check the last sent email has our attachment.
    $captured_emails = $this->container->get('state')->get('system.test_mail_collector') ?: [];
    $email = end($captured_emails);
    $firstAttachment = $email['params']['attachments'][0];
    $this->assertSame($firstAttachment->uri, $file->uri);
    $this->assertSame($firstAttachment->filename, $file->filename);
    $this->assertSame($firstAttachment->filemime, $file->filemime);
  }

}
