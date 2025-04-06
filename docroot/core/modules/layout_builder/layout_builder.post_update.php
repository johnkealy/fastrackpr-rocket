<?php

/**
 * @file
 * Post update functions for Layout Builder.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionComponent;

/**
 * Implements hook_removed_post_updates().
 */
function layout_builder_removed_post_updates() {
  return [
    'layout_builder_post_update_rebuild_plugin_dependencies' => '9.0.0',
    'layout_builder_post_update_add_extra_fields' => '9.0.0',
    'layout_builder_post_update_section_storage_context_definitions' => '9.0.0',
    'layout_builder_post_update_overrides_view_mode_annotation' => '9.0.0',
    'layout_builder_post_update_cancel_link_to_discard_changes_form' => '9.0.0',
    'layout_builder_post_update_remove_layout_is_rebuilding' => '9.0.0',
    'layout_builder_post_update_routing_entity_form' => '9.0.0',
    'layout_builder_post_update_discover_blank_layout_plugin' => '9.0.0',
    'layout_builder_post_update_routing_defaults' => '9.0.0',
    'layout_builder_post_update_discover_new_contextual_links' => '9.0.0',
    'layout_builder_post_update_fix_tempstore_keys' => '9.0.0',
    'layout_builder_post_update_section_third_party_settings_schema' => '9.0.0',
    'layout_builder_post_update_layout_builder_dependency_change' => '9.0.0',
    'layout_builder_post_update_update_permissions' => '9.0.0',
    'layout_builder_post_update_make_layout_untranslatable' => '9.0.0',
    'layout_builder_post_update_override_entity_form_controller' => '10.0.0',
    'layout_builder_post_update_section_storage_context_mapping' => '10.0.0',
    'layout_builder_post_update_tempstore_route_enhancer' => '10.0.0',
  ];
}

/**
 * Adds the layout translation settings field.
 */
function layout_builder_post_update_add_translation_field() {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
  $field_manager = \Drupal::service('entity_field.manager');
  $field_map = $field_manager->getFieldMap();
  foreach ($field_map as $entity_type_id => $field_infos) {
    if (isset($field_infos[OverridesSectionStorage::FIELD_NAME]['bundles'])) {
      foreach ($field_infos[OverridesSectionStorage::FIELD_NAME]['bundles'] as $bundle) {
        // The field map can contain stale information. If the field does not
        // exist, ignore it. The field map will be rebuilt when the cache is
        // cleared at the end of the update process.
        if (!FieldConfig::loadByName($entity_type_id, $bundle, OverridesSectionStorage::FIELD_NAME)) {
          continue;
        }
        _layout_builder_add_translation_field($entity_type_id, $bundle);

      }
    }

  }
}

/**
 * Adds a layout translation field to a given bundle.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param string $bundle
 *   The bundle.
 */
function _layout_builder_add_translation_field($entity_type_id, $bundle) {
  $field_name = OverridesSectionStorage::TRANSLATED_CONFIGURATION_FIELD_NAME;
  $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
  if (!$field) {
    $field_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'entity_type' => $entity_type_id,
        'field_name' => $field_name,
        'type' => 'layout_translation',
        'locked' => TRUE,
      ]);
      $field_storage->setTranslatable(TRUE);
      $field_storage->save();
    }

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => t('Layout Labels'),
    ]);
    $field->save();
  }
}

/**
 * Update timestamp formatter settings for Layout Builder fields.
 */
function layout_builder_post_update_timestamp_formatter(?array &$sandbox = NULL): void {
  /** @var \Drupal\Core\Field\FormatterPluginManager $field_formatter_manager */
  $field_formatter_manager = \Drupal::service('plugin.manager.field.formatter');

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $entity_view_display) use ($field_formatter_manager): bool {
    $update = FALSE;
    if ($entity_view_display instanceof LayoutEntityDisplayInterface && $entity_view_display->isLayoutBuilderEnabled()) {
      foreach ($entity_view_display->getSections() as $section) {
        foreach ($section->getComponents() as $component) {
          if (str_starts_with($component->getPluginId(), 'field_block:')) {
            $configuration = $component->get('configuration');
            $formatter =& $configuration['formatter'];
            if ($formatter && isset($formatter['type'])) {
              $plugin_definition = $field_formatter_manager->getDefinition($formatter['type'], FALSE);
              // Check also potential plugins extending TimestampFormatter.
              if ($plugin_definition && is_a($plugin_definition['class'], TimestampFormatter::class, TRUE)) {
                if (!isset($formatter['settings']['tooltip']) || !isset($formatter['settings']['time_diff'])) {
                  $update = TRUE;
                  // No need to check the rest of components.
                  break 2;
                }
              }
            }
          }
        }
      }
    }
    return $update;
  });
}

/**
 * Enable the expose all fields feature flag module.
 */
function layout_builder_post_update_enable_expose_field_block_feature_flag(): void {
  \Drupal::service('module_installer')->install(['layout_builder_expose_all_field_blocks']);
}

/**
 * Add third_party_settings key to all section components.
 */
function layout_builder_post_update_section_component_third_party(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewDisplayInterface $display) {
    $needs_update = FALSE;

    // Only update entity view displays where Layout Builder is enabled.
    if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
      foreach ($display->getSections() as $section) {
        // Add a third_party_settings element to each section component.
        $components = $section->getComponents();
        foreach ($components as $delta => $component) {
          $components[$delta] = new SectionComponent(
            $component->getUuid(),
            $component->getRegion(),
            $component->getConfiguration(),
            $component->toArray()['additional']
          );
          // Depending on the state of the configuration of a site and when this
          // update is run, there might already be third party settings on a
          // section component. Retain them, if they exist.
          $tps_providers = $component->getThirdPartyProviders();
          foreach ($tps_providers as $provider) {
            foreach ($component->getThirdPartySettings($provider) as $key => $value) {
              $component->setThirdPartySetting($provider, $key, $value);
            }
          }
          // Flag this display as needing to be updated.
          $needs_update = TRUE;
        }
      }
    }

    return $needs_update;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}

/**
 * Clear caches due to new routes and UI elements.
 */
function layout_builder_post_update_section_move_form() {
  // Empty post-update hook.
}
