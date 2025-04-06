<?php

/**
 * @file
 * The additional settings for the local environment.
 */

define('ROCKETSHIP_PROJECT_ENVIRONMENT', 'local');
define('ROCKETSHIP_MEMCACHE_READY_FOR_USE', FALSE);
define('ROCKETSHIP_PURGE_READY_FOR_USE', FALSE);

require DRUPAL_ROOT . '/../etc/drupal/general.settings.php';

// Configure config split directory.
$config['config_split.config_split.local']['status'] = TRUE;
$config['config_split.config_split.dev']['status'] = FALSE;
$config['config_split.config_split.staging']['status'] = FALSE;
$config['config_split.config_split.live']['status'] = FALSE;

// Load development services.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Skip file permissions hardening on local.
$settings['skip_permissions_hardening'] = TRUE;
