<?php

namespace Drupal\layout_builder_restrictions_by_role\Plugin\LayoutBuilderRestriction;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder_restrictions\Plugin\LayoutBuilderRestrictionBase;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\layout_builder_restrictions\Traits\PluginHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * EntityViewModeRestriction Plugin.
 *
 * @LayoutBuilderRestriction(
 *   id = "restriction_by_role",
 *   title = @Translation("Per Role"),
 *   description = @Translation("Restrict blocks/layouts per role"),
 * )
 */
class RestrictionByRole extends LayoutBuilderRestrictionBase {

  use PluginHelperTrait;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $memoryCache;


  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $class->currentUser = $container->get('current_user');
    $class->moduleHandler = $container->get('module_handler');
    $class->database = $container->get('database');
    $class->memoryCache = $container->get('entity.memory_cache');
    return $class;
  }

  /**
   * {@inheritDoc}
   */
  public function alterBlockDefinitions(array $definitions, array $context) {
    if (!isset($context['delta'])) {
      return $definitions;
    }
    if (!isset($context['section_storage'])) {
      return $definitions;
    }

    $default = $context['section_storage'] instanceof OverridesSectionStorageInterface ? $context['section_storage']->getDefaultSectionStorage() : $context['section_storage'];

    $third_party_settings = [];
    if ($default instanceof ThirdPartySettingsInterface) {
      $third_party_settings = $default->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_per_role', []);
    }

    if (empty($third_party_settings['override_defaults'])) {
      // Replace it with defaults.
      $third_party_settings = \Drupal::config('layout_builder_restrictions_by_role.settings')
        ->getRawData();
    }

    if (empty($third_party_settings)) {
      // This entity has no restrictions. Look no further.
      return $definitions;
    }

    $layout_id = $context['section_storage']->getSection($context['delta'])
      ->getLayoutId();

    foreach ($definitions as $delta => $definition) {
      if (!$this->isBlockAllowed($delta, $definition, $layout_id, $third_party_settings)) {
        unset($definitions[$delta]);
      }
    }

    return $definitions;
  }

  /**
   * {@inheritDoc}
   */
  public function alterSectionDefinitions(array $definitions, array $context) {
    // Respect restrictions on allowed layouts specified by section storage.
    if (!isset($context['section_storage'])) {
      return $definitions;
    }

    $default = $context['section_storage'] instanceof OverridesSectionStorageInterface ? $context['section_storage']->getDefaultSectionStorage() : $context['section_storage'];
    if ($default instanceof ThirdPartySettingsInterface) {
      $third_party_settings = $default->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_per_role', []);
      if (empty($third_party_settings['override_defaults'])) {
        // Replace it with defaults.
        $third_party_settings = \Drupal::config('layout_builder_restrictions_by_role.settings')
          ->getRawData();
      }
      if (!isset($third_party_settings['layout_restriction']) || $third_party_settings['layout_restriction'] === 'all') {
        // No layout specific restrictions present.
        return $definitions;
      }
      foreach ($definitions as $layout_id => $definition) {
        $allowedForRoles = $this->currentUser->getRoles(TRUE);
        foreach ($allowedForRoles as $key => $role) {
          if (empty($third_party_settings['allowed_layouts'][$layout_id][$role])) {
            // This layout is not allowed for this role.
            unset($allowedForRoles[$key]);
          }
        }
        // Remove the layout as an option if no roles allow it.
        if (empty($allowedForRoles)) {
          unset($definitions[$layout_id]);
        }
      }
    }

    return $definitions;
  }

  /**
   * {@inheritDoc}
   */
  public function blockAllowedinContext(SectionStorageInterface $section_storage, $delta_from, $delta_to, $region_to, $block_uuid, $preceding_block_uuid = NULL) {
    $view_display = $this->getValuefromSectionStorage([$section_storage], 'view_display');
    $third_party_settings = $view_display->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_per_role', []);
    if (empty($third_party_settings['override_defaults'])) {
      // Replace it with defaults.
      $third_party_settings = \Drupal::config('layout_builder_restrictions_by_role.settings')
        ->getRawData();
    }
    if (empty($third_party_settings)) {
      // This entity has no restrictions. Look no further.
      return TRUE;
    }

    $bundle = $this->getValuefromSectionStorage([$section_storage], 'bundle');

    // Get "from" section and layout id. (not needed?)
    $section_from = $section_storage->getSection($delta_from);

    // Get "to" section and layout id.
    $section_to = $section_storage->getSection($delta_to);
    $layout_id_to = $section_to->getLayoutId();

    // Get block information.
    $component = $section_from->getComponent($block_uuid)->toArray();
    $block_id = $component['configuration']['id'];

    // Load the plugin definition.
    if ($definition = $this->blockManager()->getDefinition($block_id)) {

      if (!$this->isBlockAllowed($block_id, $definition, $layout_id_to, $third_party_settings)) {
        return $this->t('There is a restriction on %block placement in the %layout %region region for %type content.', [
          '%block' => $definition['admin_label'],
          '%layout' => $layout_id_to,
          '%region' => $region_to,
          '%type' => $bundle,
        ]);
      }
    }

    // Default: this block is not restricted.
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function inlineBlocksAllowedinContext(SectionStorageInterface $section_storage, $delta, $region) {
    $view_display = $this->getValuefromSectionStorage([$section_storage], 'view_display');
    $section = $section_storage->getSection($delta);
    $layout_id = $section->getLayoutId();
    $third_party_settings = $view_display->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_per_role', []);
    if (empty($third_party_settings['override_defaults'])) {
      // Replace it with defaults.
      $third_party_settings = \Drupal::config('layout_builder_restrictions_by_role.settings')
        ->getRawData();
    }
    $inline_blocks = $this->getInlineBlockPlugins();
    foreach ($inline_blocks as $key => $block) {
      if (!$this->isBlockAllowed($block, [
        'category' => 'Inline blocks',
        'provider' => '',
      ], $layout_id,
        $third_party_settings)) {
        unset($inline_blocks[$key]);
      }
    }
    return $inline_blocks;
  }

  /**
   * Helper function to retrieve uuid->type keyed block array.
   *
   * @return string[]
   *   A key-value array of uuid-block type.
   */
  private function getBlockTypeByUuid() {
    $cached = $this->memoryCache->get('block_types_by_uid');

    if ($cached) {
      return $cached->data;
    }

    if ($this->moduleHandler->moduleExists('block_content')) {
      // Pre-load all reusable blocks by UUID to retrieve block type.
      $query = $this->database->select('block_content', 'b')
        ->fields('b', ['uuid', 'type']);
      $results = $query->execute();
      $keyed_results = $results->fetchAllKeyed();
      $this->memoryCache->set('block_types_by_uid', $keyed_results);

      return $keyed_results;
    }

    return [];
  }

  /**
   * @param string $block_id
   *   The block ID of the block to check
   * @param array $definition
   *   Array containing block provider and category
   * @param $layout_id
   *   The plugin ID of the layout the block is to be placed in
   * @param $third_party_settings
   *   The settings governing this plugin
   *
   * @return bool
   *   Whether or not this block is allowed to be placed in the given layout
   *   by the current user.
   */
  protected function isBlockAllowed(string $block_id, $definition, $layout_id, $third_party_settings) {
    $access = [];

    foreach ($this->currentUser->getRoles(TRUE) as $role) {
      // Check access for this block for each role.
      $access[$role] = $this->isBlockAllowedForRole($block_id, $definition, $layout_id, $third_party_settings, $role);
    }

    $access = array_filter($access);

    // If any role allows it, allow it.
    return !empty($access);
  }

  /**
   * @param string $block_id
   * @param $definition
   * @param $layout_id
   * @param $third_party_settings
   * @param $role
   *
   * @return bool
   */
  protected function isBlockAllowedForRole(string $block_id, $definition, $layout_id, $third_party_settings, $role) {
    $content_block_types_by_uuid = $this->getBlockTypeByUuid();

    $category = $this->getUntranslatedCategory($definition['category']);
    // Custom blocks get special treatment.
    if ($definition['provider'] == 'block_content') {
      // 'Custom block types' are disregarded if 'Custom blocks'
      // restrictions are enabled.
      $category = 'Custom blocks';
      if (!isset($third_party_settings['__blocks'][$role]['Custom blocks']['restriction_type']) || $third_party_settings['__blocks'][$role]['Custom blocks']['restriction_type'] === 'all') {
        // No custom restrictions for custom blocks so types can be checked.
        $category = 'Custom block types';
        $block_id_exploded = explode(':', $block_id);
        $uuid = $block_id_exploded[1];
        $block_id = $content_block_types_by_uuid[$uuid];
      }
    }

    $restriction_type = $third_party_settings['__blocks'][$role][$category]['restriction_type'] ?? 'all';
    // if all, then continue?
    switch ($restriction_type) {
      case 'all':
        // Not restricted.
        break;
      case 'whitelisted':
        if (empty($third_party_settings['__blocks'][$role][$category]['restrictions'][$block_id])) {
          // Not whitelisted, get rid of it.
          // See if it's whitelisted in specific layout first.
          return $this->checkLayoutSpecificOverrideBeforeDenying($third_party_settings, $layout_id, $role, $category, $block_id);
        }
        break;
      case 'blacklisted':
        if (!empty($third_party_settings['__blocks'][$role][$category]['restrictions'][$block_id])) {
          // Blacklisted, get rid of it.
          // See if it's whitelisted in specific layout first.
          return $this->checkLayoutSpecificOverrideBeforeDenying($third_party_settings, $layout_id, $role, $category, $block_id);
        }
        break;
    }
    // Ok, done checking "all" restriction, if still here check layout specific setting.
    if (!isset($third_party_settings['layout_restriction']) || $third_party_settings['layout_restriction'] === 'all') {
      // No layout specific restrictions present.
      return TRUE;
    }
    if (empty($third_party_settings['allowed_layouts'][$layout_id][$role])) {
      // This layout is not allowed for this role. Hence, no blocks should be available for this layout?
      return FALSE;
    }
    // This layout is allowed, let's see if the current block is allowed for this layout/role combination.
    $restriction_type = $third_party_settings['__layouts'][$layout_id][$role][$category]['restriction_type'] ?? 'all';
    // if all, then continue?
    switch ($restriction_type) {
      case 'all':
        // Not restricted.
        break;
      case 'whitelisted':
        if (empty($third_party_settings['__layouts'][$layout_id][$role][$category]['restrictions'][$block_id])) {
          // Not whitelisted, get rid of it.
          return FALSE;
        }
        break;
      case 'blacklisted':
        if (!empty($third_party_settings['__layouts'][$layout_id][$role][$category]['restrictions'][$block_id])) {
          // Blacklisted, get rid of it.
          return FALSE;
        }
        break;
    }
    return TRUE;
  }

  /**
   * @param $third_party_settings
   * @param $layout_id
   * @param $role
   * @param string $category
   * @param string $block_id
   *
   * @return bool
   */
  protected function checkLayoutSpecificOverrideBeforeDenying($third_party_settings, $layout_id, $role, string $category, string $block_id) {
    if (!isset($third_party_settings['layout_restriction']) || $third_party_settings['layout_restriction'] === 'all') {
      // No layout specific restrictions present.
      return FALSE;
    }
    if (empty($third_party_settings['allowed_layouts'][$layout_id][$role])) {
      // This layout is not allowed for this role. Hence, no blocks should be available for this layout.
      return FALSE;
    }
    // Check if the current block is allowed for this layout/role combination.
    $restriction_type = $third_party_settings['__layouts'][$layout_id][$role][$category]['restriction_type'] ?? 'all';
    if ($restriction_type === 'whitelisted' && !empty($third_party_settings['__layouts'][$layout_id][$role][$category]['restrictions'][$block_id])) {
      // Explicitly whitelisted for this layout.
      return TRUE;
    }
    return FALSE;
  }

}
