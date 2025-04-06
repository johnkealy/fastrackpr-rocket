<?php

namespace Drupal\layout_builder_modal;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Handles contextual metadata for Layout Builder Modal.
 */
class LayoutBuilderModal implements TrustedCallbackInterface {

  /**
   * Adds contextual link metadata for Layout Builder Modal.
   *
   * @param array $element
   *   The Layout Builder render element.
   *
   * @return array
   *   The modified Layout Builder render element.
   */
  public static function preRenderContextual(array $element) {
    $config = \Drupal::config('layout_builder_modal.settings');

    $hash = hash('sha256', serialize($config->getRawData()));

    foreach ($element['layout_builder'] as &$child_element) {
      if (isset($child_element['layout-builder__section'])) {
        /** @var \Drupal\Core\Layout\LayoutDefinition $layout_definition */
        $layout_definition = $child_element['layout-builder__section']['#layout'];

        foreach ($layout_definition->getRegions() as $region => $info) {
          if (empty($child_element['layout-builder__section'][$region])) {
            continue;
          }
          foreach ($child_element['layout-builder__section'][$region] as &$section_child_element) {
            if (isset($section_child_element['#theme']) && $section_child_element['#theme'] === 'block') {
              // Search for layout_builder array keys.
              $layout_builder_elements = array_filter(array_keys($section_child_element['#contextual_links']), static function ($key) {
                return strpos($key, 'layout_builder_') === 0;
              });
              if (($layout_builder_element_key = array_shift($layout_builder_elements)) !== NULL) {
                // Set hash value to first layout builder element.
                $section_child_element['#contextual_links'][$layout_builder_element_key]['metadata']['layout_builder_modal'] = $hash;
              }
            }
          }
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderContextual',
    ];
  }

}
