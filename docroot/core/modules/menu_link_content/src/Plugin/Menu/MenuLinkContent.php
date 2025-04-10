<?php

namespace Drupal\menu_link_content\Plugin\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Menu\MenuLinkTranslationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the menu link plugin for content menu links.
 */
class MenuLinkContent extends MenuLinkBase implements ContainerFactoryPluginInterface, MenuLinkTranslationInterface {

  /**
   * Entities IDs to load.
   *
   * It is an array of entity IDs keyed by entity IDs.
   *
   * @var array
   */
  protected static $entityIdsToLoad = [];

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'menu_name' => 1,
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'enabled' => 1,
    'title' => 1,
    'description' => 1,
    'route_name' => 1,
    'route_parameters' => 1,
    'url' => 1,
    'options' => 1,
  ];

  /**
   * The menu link content entity connected to this plugin instance.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $entity;

  /**
   * An array of entity operations links.
   *
   * @var array
   */
  protected $listBuilderOperations;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new MenuLinkContent.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
      $entity_id = $this->pluginDefinition['metadata']['entity_id'];
      // Builds a list of entity IDs to take advantage of the more efficient
      // EntityStorageInterface::loadMultiple() in getEntity() at render time.
      static::$entityIdsToLoad[$entity_id] = $entity_id;
    }

    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * Loads the entity associated with this menu link.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The menu link content entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the entity ID and UUID are both invalid or missing.
   */
  public function getEntity(): MenuLinkContentInterface {
    if (empty($this->entity)) {
      $entity = NULL;
      $storage = $this->entityTypeManager->getStorage('menu_link_content');
      if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
        $entity_id = $this->pluginDefinition['metadata']['entity_id'];
        // Make sure the current ID is in the list, since each plugin empties
        // the list after calling loadMultiple(). Note that the list may include
        // multiple IDs added earlier in each plugin's constructor.
        static::$entityIdsToLoad[$entity_id] = $entity_id;
        $entities = $storage->loadMultiple(array_values(static::$entityIdsToLoad));
        $entity = $entities[$entity_id] ?? NULL;
        static::$entityIdsToLoad = [];
      }
      if (!$entity) {
        // Fallback to the loading by the UUID.
        $uuid = $this->getUuid();
        $entity = $this->entityRepository->loadEntityByUuid('menu_link_content', $uuid);
      }
      if (!$entity) {
        throw new PluginException("Entity not found through the menu link plugin definition and could not fallback on UUID '$uuid'");
      }
      // Clone the entity object to avoid tampering with the static cache.
      $this->entity = clone $entity;
      $the_entity = $this->entityRepository->getTranslationFromContext($this->entity);
      /** @var \Drupal\menu_link_content\MenuLinkContentInterface $the_entity */
      $this->entity = $the_entity;
      $this->entity->setInsidePlugin();
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // We only need to get the title from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->getTitle();
    }
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // We only need to get the description from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->getDescription();
    }
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    $operations = $this->getListBuilderOperations();
    return isset($operations['delete']) ? $operations['delete']['url'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    $operations = $this->getListBuilderOperations();
    return isset($operations['edit']) ? $operations['edit']['url'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    $operations = $this->getListBuilderOperations();
    return isset($operations['translate']) ? $operations['translate']['url'] : NULL;
  }

  /**
   * Load entity operations from the list builder.
   *
   * @return array
   *   An array of operations.
   */
  protected function getListBuilderOperations() {

    if (is_null($this->listBuilderOperations)) {
      $this->listBuilderOperations = $this->entityTypeManager
        ->getListBuilder($this->getEntity()->getEntityTypeId())
        ->getOperations($this->getEntity());
    }

    return $this->listBuilderOperations;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(): array {
    return $this->getListBuilderOperations();
  }

  /**
   * Returns the unique ID representing the menu link.
   *
   * @return string
   *   The menu link ID.
   */
  protected function getUuid() {
    return $this->getDerivativeId();
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();
    if ($persist) {
      $entity = $this->getEntity();
      foreach ($overrides as $key => $value) {
        $entity->{$key}->value = $value;
      }
      $entity->save();
    }

    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->getEntity()->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation($langcode) : bool {
    $entity = $this->getEntity();

    if (!$entity->hasTranslation($langcode)) {
      return FALSE;
    }

    return (bool) $entity->getTranslation($langcode)->content_translation_status->value;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    $this->getEntity()->delete();
  }

}
