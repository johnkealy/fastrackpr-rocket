<?php

namespace Drupal\Core\Menu;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a menu link language manipulator.
 */
class LanguageMenuLinkManipulator {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * LanguageMenuLinkManipulator constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Hide menu links that do not have translation for the current language.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function filterLanguage(array $tree) : array {
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    foreach ($tree as $key => $link) {
      if ($link->link instanceof MenuLinkTranslationInterface) {
        // If the link is translatable, but has no translation, hide it.
        if ($link->link->isTranslatable() && !$link->link->hasTranslation($current_language)) {
          unset($tree[$key]);
        }
        elseif ($link->hasChildren) {
          // Recursively call this method to filter out untranslated children.
          $tree[$key]->subtree = $this->filterLanguage($link->subtree);
        }
      }
    }
    return $tree;
  }

}
