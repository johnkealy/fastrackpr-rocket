<?php

namespace Drupal\unified_twig_ext\TwigExtension;

/**
 * Loads twig customizations from a dist directory.
 */
class ExtensionLoader {

  /**
   * The list of loaded filters, functions and tags.
   *
   * @var array
   */
  protected static $objects = [];

  /**
   * Loads a singleton registry of plugin objects.
   */
  public static function init() {
    if (!self::$objects) {
      static::loadAll('filters');
      static::loadAll('functions');
      static::loadAll('tags');
    }
  }

  /**
   * Gets all plugin objects of a given type.
   *
   * @param string $type
   *   The plugin type to load.
   *
   * @return array
   *   An array of loaded objects to be provided by the twig extension for a
   *   given type.
   */
  public static function get($type) {
    return !empty(self::$objects[$type]) ? self::$objects[$type] : [];
  }

  /**
   * Loads all plugins of a given type.
   *
   * This should be called once per $type.
   *
   * @param string $type
   *   The type to load all plugins for.
   */
  protected static function loadAll($type) {
    $theme = \Drupal::config('system.theme')->get('default');
    $themeLocation = \Drupal::service('extension.list.theme')->getPath($theme);
    $themePath = DRUPAL_ROOT . '/' . $themeLocation . '/';

    $extensionPaths = glob($themePath . '*/_twig-components/');

    foreach ($extensionPaths as $extensionPath) {
      $fullPath = $extensionPath;
      foreach (scandir($fullPath . $type) as $file) {
        $fileInfo = pathinfo($file);
        if ($fileInfo['extension'] === 'php') {
          if ($file[0] != '.' && $file[0] != '_' && substr($file, 0, 3) != 'pl_') {
            static::load($type, $fullPath . $type . '/' . $file);
          }
        }
      }
    }
  }

  /**
   * Loads a specific plugin instance.
   *
   * @param string $type
   *   The type of the plugin to be loaded.
   * @param string $file
   *   The fully qualified path of the plugin to be loaded.
   */
  protected static function load($type, $file) {
    include $file;
    switch ($type) {
      case 'filters':
        if (isset($filter)) {
          self::$objects['filters'][] = $filter;
        }
        break;

      case 'functions':
        if (isset($function)) {
          self::$objects['functions'][] = $function;
        }
        break;

      case 'tags':
        if (preg_match('/^([^\.]+)\.tag\.php$/', basename($file), $matches)) {
          $class = "Project_{$matches[1]}_TokenParser";
          if (class_exists($class)) {
            self::$objects['parsers'][] = new $class();
          }
        }
        break;
    }
  }

}
