<?php

namespace Drupal\rocketship_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formatter to load the field_description if not replaced.
 *
 * Plugin implementation of the 'contentblock_description_replacement_formatter'
 * formatter.
 *
 * @FieldFormatter(
 *   id = "contentblock_description_replacement_formatter",
 *   label = @Translation("Description Replacement Formatter"),
 *   field_types = {
 *     "contentblock_description_replacement"
 *   }
 * )
 */
class ContentBlockDescriptionReplacementFormatter extends TextDefaultFormatter {

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $class->entityRepository = $container->get('entity.repository');
    $class->renderer = $container->get('renderer');
    $class->routeMatch = $container->get('current_route_match');
    $class->entityTypeManager = $container->get('entity_type.manager');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    return $summary;
  }

  /**
   * Fetch the content entity object from the current route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The currently viewed content entity object.
   */
  protected function fetchEntityFromRoute(): ?ContentEntityInterface {
    $entity_type_id = $this->getEntityTypeFromRoute();
    if ($entity_type_id === NULL) {
      return NULL;
    }
    $entity = $this->routeMatch->getParameter($entity_type_id);
    if ($entity === NULL) {
      $subject = $this->routeMatch->getRawParameter('section_storage') ?? '';
      preg_match('/' . $entity_type_id . '\.([0-9]+)/', $subject, $matches);
      if (!empty($matches[1])) {
        $entity = $matches[1];
      }
    }

    // Get node from section storage if node is not available in the route.
    if (is_numeric($entity)) {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)
        ->load($entity);
    }

    return $entity;
  }

  /**
   * Fetch the entity type from the current route.
   *
   * @return string|null
   *   The currently viewed content entity type.
   */
  protected function getEntityTypeFromRoute(): ?string {
    $route_name = $this->routeMatch->getRouteName();
    $parts = explode('.', $route_name);
    if ($parts[0] === 'entity') {
      return $parts[1];
    }
    if ($parts[0] === 'layout_builder' && $parts[1] === 'overrides') {
      return $parts[2];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // Note: don't trust the passed langcode, it is for the block which may
    // always be EN, it may match the current language, it may be anything
    // so don't use it, rely on entity repository for the entity itself.
    // If the description is being replaced, the correct value is already inside
    // $items anyway.
    $route_name = $this->routeMatch->getRouteName();
    // Since on first save the items is empty, we need to add some fallback.
    if ($items->count() === 0) {
      $item = new \stdClass();
      $item->replace = FALSE;
      $item->value = '';
      $items = [$item];
    }
    foreach ($items as $delta => $item) {
      $value = $item->value;
      $entity = $this->fetchEntityFromRoute();
      $entity_type_id = $this->getEntityTypeFromRoute();

      // Show the current entity description.
      if (!$item->replace && $entity instanceof EntityInterface) {
        switch ($route_name) {
          case "entity.{$entity_type_id}.canonical":
            $entity = $this->entityRepository->getCanonical($entity_type_id, $entity->id());
            $entity = $this->entityRepository->getTranslationFromContext($entity);
            $value = $entity->get('field_description')->value;
            $this->renderer->addCacheableDependency($elements, $entity);
            break;

          case 'entity.node.revision':
            $revision_id = $this->routeMatch
              ->getParameter('node_revision');

            if ($revision_id instanceof NodeInterface) {
              $entity = $revision_id;
            }
            else {
              $entity = $this->entityTypeManager
                ->getStorage('node')
                ->loadRevision($revision_id);
            }

            $entity = $this->entityRepository->getTranslationFromContext($entity);
            $value = $entity->get('field_description')->value;
            $this->renderer->addCacheableDependency($elements, $entity);
            break;

          case (bool) preg_match('/layout_builder.*/', $route_name):
          case 'entity.node.latest_version':
            $entity = $this->entityRepository->getActive($entity_type_id, $entity->id());
            $entity = $this->entityRepository->getTranslationFromContext($entity);
            $value = $entity->get('field_description')->value;
            $this->renderer->addCacheableDependency($elements, $entity);
            break;

          case 'diff.revisions_diff':
            $revision_id = $this->routeMatch
              ->getParameter('right_revision');
            $entity = $this->entityTypeManager
              ->getStorage($entity_type_id)
              ->loadRevision($revision_id);
            $entity = $this->entityRepository->getTranslationFromContext($entity);
            $value = $entity->get('field_description')->value;
            $this->renderer->addCacheableDependency($elements, $entity);
            break;

          default:
            $value = $this->t('Placeholder for replacement description');
            // Don't cache.
            $this->renderer->addCacheableDependency($elements, new \stdClass());
            break;
        }
      }

      // Fallback value.
      if (!$entity && !$item->replace && !$value) {
        $value = $this->t('Placeholder for replacement description');
        // Don't cache.
        $this->renderer->addCacheableDependency($elements, new \stdClass());
      }

      $elements[$delta] = [
        '#markup' => $value,
      ];
    }

    return $elements;
  }

}
