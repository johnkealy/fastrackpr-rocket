<?php

namespace Drupal\Tests\node_keep\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for node_keep.
 *
 * @group node_keep
 */
class NodeKeepGenericTest extends GenericModuleTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'node_keep_config_test',
  ];

  /**
   * {@inheritDoc}
   */
  protected function assertHookHelp(string $module): void {
    // Don't do anything here. Just overwrite this useless method, so we do
    // don't have to implement hook_help().
  }

}
