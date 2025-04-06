<?php

namespace Drupal\node_keep_token;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Service class for node_keep_token helper serivce.
 */
class NodeKeepTokenService {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new NodeKeepTokenService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Load all protected nodes which have a machine name set, keyed by node id.
   *
   * @return array
   *   An array containing details of fetched nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProtectedNodes() {
    $data = [];

    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties(['node_keeper' => 1]);

    foreach ($nodes as $node) {
      if ($node->keeper_machine_name->value) {
        $data[$node->id()] = [
          'label' => $node->label(),
          'machine_name' => $node->keeper_machine_name->value,
          'id' => $node->id(),
        ];
      }
    }

    return $data;
  }

  /**
   * Load all protected nodes by specific keys and values.
   *
   * @return array
   *   An array containing details of fetched nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProtectedNodesAsOptions($key2use = 'machine_name', $value2use = 'machine_name') {
    $data = [];

    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties(['node_keeper' => 1]);

    foreach ($nodes as $node) {
      if ($node->keeper_machine_name->value) {
        switch ($key2use) {
          case "machine_name":
            $key = $node->keeper_machine_name->value;
            break;

          case "id":
          default:
            $key = $node->id();
            break;
        }
        switch ($value2use) {
          case "id":
            $value = $node->id();
            break;

          case "label":
            $value = $node->label();
            break;

          case "machine_name":
          default:
            $value = $node->keeper_machine_name->value;
            break;
        }

        $data[$key] = $value;
      }
    }

    return $data;
  }

  /**
   * Lists protected nodes which have a machine name set, keyed by machine name.
   *
   * @return array
   *   An array containing details of fetched nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProtectedMachineNames() {
    $data = [];

    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'node_keeper' => 1,
      ]);

    foreach ($nodes as $node) {
      if ($node->keeper_machine_name->value) {
        $data[$node->keeper_machine_name->value] = [
          'label' => $node->label(),
          'machine_name' => $node->keeper_machine_name->value,
          'id' => $node->id(),
        ];
      }
    }

    return $data;
  }

  /**
   * Find a protected node based on the machine name set in the UI.
   *
   * @param string $machine_name
   *   Machine name used to load the node.
   * @param int $exception_nid
   *   Exception node nid.
   *
   * @return bool
   *   Whether it is used or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isMachineNameUsed($machine_name, $exception_nid) {
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'node_keeper' => 1,
        'keeper_machine_name' => $machine_name,
      ]);

    unset($nodes[$exception_nid]);

    return count($nodes);
  }

  /**
   * Find a protected node based on the machine name set in the UI.
   *
   * @param string $machine_name
   *   Machine name used to load the node.
   * @param bool $language_fallback
   *   Whether to return to default language if translation is not found.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Fetched node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProtectedNodeByMachineName($machine_name, bool $language_fallback = TRUE) {
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'node_keeper' => 1,
        'keeper_machine_name' => $machine_name,
      ]);

    $node = NULL;
    if (count($nodes)) {
      $node = reset($nodes);
      $lang_code = $this->languageManager->getCurrentLanguage()->getId();
      if ($node->hasTranslation($lang_code)) {
        $node = $node->getTranslation($lang_code);
      }
      elseif (!$language_fallback) {
        $node = NULL;
      }
    }

    return $node;
  }

}
