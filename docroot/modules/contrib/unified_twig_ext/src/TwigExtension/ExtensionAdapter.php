<?php

namespace Drupal\unified_twig_ext\TwigExtension;

use Twig\Extension\AbstractExtension;

/**
 * Adapts pattern-lab extensions to Drupal.
 */
class ExtensionAdapter extends AbstractExtension {

  /**
   * Creates the adapter twig extension.
   *
   * This will load from the default pattern-lab twig extension locations.
   */
  public function __construct() {
    ExtensionLoader::init();
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return ExtensionLoader::get('functions');
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return ExtensionLoader::get('filters');
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return ExtensionLoader::get('parsers');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'unified_twig_ext_adapter';
  }

}
