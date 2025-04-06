<?php

namespace Drupal\Tests\node_keep\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods specifically for testing something.
 *
 * @group node_keep
 */
class NodeKeepGeneralTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'test_page_test',
    'node_keep',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->createContentType(['type' => 'article']);
    $this->user = $this->drupalCreateUser([
      'access content',
      'delete any article content',
      'edit any article content',
    ]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if installing the module, won't break the site.
   */
  public function testInstallation() {

    $session = $this->assertSession();
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    // Go to uninstallation page an uninstall node_keep:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-node-keep');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
    // Retest the frontpage:
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests the modules uninstallation, when prevention is enabled.
   */
  public function testUninstallationWhenPreventionActivated() {
    // Create a node with the node_keep delete prevention enabled:
    $this->createNode([
      'type' => 'article',
      'title' => 'test123',
      'node_keeper' => [
        0 => [
          'value' => 1,
        ],
      ],
    ]);

    // Go to uninstallation page an uninstall node_keep:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-node-keep');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
    // Retest the frontpage:
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests the node delete prevention.
   */
  public function testNodeDeletePrevention() {
    $session = $this->assertSession();
    $this->drupalLogout();
    $this->drupalLogin($this->user);

    // Create a node with the node_keep delete prevention enabled:
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'test123',
      'node_keeper' => [
        0 => [
          'value' => 1,
        ],
      ],
    ]);
    // The normal user does not have the 'administer node_keep per node'
    // So access should be denied and a message displayed:
    $this->drupalGet('/node/' . $node->id() . '/delete');
    $session->statusCodeEquals(403);
    $session->pageTextContains('This content has limited access permissions. You can preview, edit and update it, but it can only be removed by an administrator');

    // The edit page should be accessible, but the same message should appear:
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('This content has limited access permissions. You can preview, edit and update it, but it can only be removed by an administrator');
  }

}
