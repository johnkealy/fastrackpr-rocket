<?php

namespace Drupal\dropsolid_rocketship_drush\Commands;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Consolidation\SiteProcess\ProcessManagerAwareInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Define custom rocketship drush commands.
 *
 * @package Drupal\dropsolid_rocketship_drush\Commands
 */
class DropsolidRocketshipCommands extends DrushCommands implements ProcessManagerAwareInterface, SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected CachedStorage $storage;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The profile extension list.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected ProfileExtensionList $profileExtensionList;

  /**
   * A new instance of DropsolidRocketshipCommands.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\CachedStorage $storage
   *   The config storage.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Extension\ProfileExtensionList $extension_list
   *   The profile extension list.
   */
  public function __construct(LanguageManagerInterface $language_manager, CachedStorage $storage, FileSystemInterface $file_system, ProfileExtensionList $extension_list) {
    parent::__construct();

    $this->languageManager = $language_manager;
    $this->storage = $storage;
    $this->fileSystem = $file_system;
    $this->profileExtensionList = $extension_list;
  }

  /**
   * Run first time setup of the configuration management system.
   *
   * It will set up default split config and then export all config splits to
   * the correct folder.
   *
   * @command rocketship:config-setup
   *
   * @usage rocketship:config-setup
   *   Setup config folder
   *
   * @validate-module-enabled dropsolid_rocketship_drush
   *
   * @aliases d-set
   *
   * @throws \Exception
   */
  public function configSetup() {
    // Make sure our settings files have been included. If not, d-set can break
    // config pretty easily if there's no overrides to properly (de)activate
    // config splits.
    if (!defined('ROCKETSHIP_PROJECT_ENVIRONMENT')) {
      throw new \Exception("It seems Rocketship's additional settings file for this environment has not yet been included. \nEnsure the correct additional_settings file in etc/drupal has been included, see the readme file at readme/after-install/readme.md for more information.");
    }

    // Loop over the install profile's split folders and import that config,
    // then export it to correctly populate the split sync folder. It's a
    // roundabout sorta messy way but it makes sure all the splits are present
    // and have the default stuff in 'em.
    $path = $this->profileExtensionList->getPath('dropsolid_rocketship_profile') . '/config/splits';
    $directories = glob($path . '/*', GLOB_ONLYDIR);

    // Before we start ex/importing things, we need to make sure the directories
    // exist for *every* split. If this command fails half-way through it can
    // mess up your splits something fierce.
    foreach ($directories as $split) {
      $id = substr($split, strrpos($split, '/') + 1);
      $split_data = $this->storage->read("config_split.config_split.$id");
      if (!$split_data) {
        // Something weird.
        throw new \Exception(t('Could not find configuration for :id split.', [':id' => $id]));
      }
      $split_location = $split_data['folder'];
      if (!$this->fileSystem->prepareDirectory($split_location, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        throw new \Exception(t(':folder is not writable.', [':folder' => $split_location]));
      }
    }

    // Standard export to populate sync folder.
    $selfRecord = $this->siteAliasManager()->getSelf();
    $options = ['yes' => TRUE];
    $process = $this->processManager()->drush($selfRecord, 'cex', [], $options);
    $process->mustRun($process->showRealtime());

    // Simply copy the files because we can not export inactive splits.
    foreach ($directories as $split) {
      $this->output()->writeln("Working on $split");
      $id = substr($split, strrpos($split, '/') + 1);
      $split_data = $this->storage->read("config_split.config_split.$id");
      foreach (new \DirectoryIterator($split) as $file) {
        if ($file->isFile()) {
          copy($file->getRealPath(), $split_data['folder'] . '/' . $file->getFilename());
        }
      }
    }
  }

}
