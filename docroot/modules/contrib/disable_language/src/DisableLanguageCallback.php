<?php

namespace Drupal\disable_language;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Disable language callback.
 */
class DisableLanguageCallback implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element): array {
    if (\Drupal::currentUser()->hasPermission('view disabled languages')) {
      return $element;
    }

    $enabled_languages = \Drupal::service('disable_language.disable_language_manager')
      ->getEnabledLanguages();
    $enabled_langcodes = array_keys($enabled_languages);
    $langcode_options = array_keys($element['#options']);

    // Only keep the enabled languages.
    foreach (array_diff($langcode_options, $enabled_langcodes) as $langcode) {
      unset($element['#options'][$langcode]);
    }

    return $element;
  }

}
