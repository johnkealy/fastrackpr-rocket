<?php

namespace Drupal\rocketship_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rocketship_core\Plugin\Field\FieldType\ContentBlockTitleReplacement;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'contentblock_title_replacement_formatter'.
 *
 * @FieldFormatter(
 *   id = "contentblock_title_replacement_formatter",
 *   label = @Translation("Title Replacement Formatter"),
 *   field_types = {
 *     "contentblock_title_replacement"
 *   }
 * )
 */
class ContentBlockTitleReplacementFormatter extends TextDefaultFormatter {

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
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
  public static function defaultSettings() {
    return [
        'wrapper_override' => 'nothing',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['wrapper_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Override wrapper selection'),
      '#description' => $this->t('Select a tag to wrap this output in, overriding the selection made by the client.'),
      '#default_value' => $this->getSetting('wrapper_override'),
      '#options' => [
        'nothing' => $this->t('Nothing'),
        'h1' => $this->t('h1'),
        'h2' => $this->t('h2'),
        'h3' => $this->t('h3'),
        'h4' => $this->t('h4'),
        'h5' => $this->t('h5'),
        'h6' => $this->t('h6'),
        'span' => $this->t('span'),
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Wrapper override: @override', ['@override' => $this->getSetting('wrapper_override')]);

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

  protected function getEntityTypeFromRoute(): ?string {
    $route_name = $this->routeMatch->getRouteName();
    $parts = explode('.', $route_name);
    if ($parts[0] === 'entity') {
      return $parts[1];
    }
    if ($parts[0] ==='layout_builder' && $parts[1] === 'overrides') {
      return $parts[2];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Grab tag that the client chose.
    $tag = $items->wrapper ?? 'h1';

    // Make sure it's a legal choice.
    if (!in_array($tag, ContentBlockTitleReplacement::getPossibleOptions())) {
      $tag = 'h1';
    }
    if ($this->getSetting('wrapper_override') !== 'nothing') {
      $tag = $this->getSetting('wrapper_override');
    }

    $value = $items->value;
    $route_name = $this->routeMatch->getRouteName();
    $entity = $this->fetchEntityFromRoute();
    $entity_type_id = $this->getEntityTypeFromRoute();

    // Show the current entity title.
    if (!$items->replace && $entity instanceof ContentEntityInterface) {
      switch ($route_name) {
        case 'entity.' . $entity_type_id . '.canonical':
          $entity = $this->entityRepository->getCanonical($entity_type_id, $entity->id());
          $entity = $this->entityRepository->getTranslationFromContext($entity);
          $value = $entity->label();
          $this->renderer->addCacheableDependency($elements, $entity);
          break;

        case 'entity.' . $entity_type_id . '.revision':
          $revision_id = $this->routeMatch
            ->getParameter($entity_type_id . '_revision');

          if ($revision_id instanceof ContentEntityInterface) {
            $entity = $revision_id;
          }
          else {
            $entity = $this->entityTypeManager
              ->getStorage($entity_type_id)
              ->loadRevision($revision_id);
          }

          $entity = $this->entityRepository->getTranslationFromContext($entity);
          $value = $entity->label();
          $this->renderer->addCacheableDependency($elements, $entity);
          break;

        case (bool) preg_match('/layout_builder.*/', $route_name):
        case 'entity.' . $entity_type_id . '.latest_version':
          $entity = $this->entityRepository->getActive($entity_type_id, $entity->id());
          $entity = $this->entityRepository->getTranslationFromContext($entity);
          $value = $entity->label();
          $this->renderer->addCacheableDependency($elements, $entity);
          break;

        case 'diff.revisions_diff':
          $revision_id = $this->routeMatch
            ->getParameter('right_revision');
          $entity = $this->entityTypeManager
            ->getStorage('node')
            ->loadRevision($revision_id);
          $entity = $this->entityRepository->getTranslationFromContext($entity);
          $value = $entity->label();
          $this->renderer->addCacheableDependency($elements, $entity);
          break;

        default:
          $value = $this->t('Placeholder for replacement title');
          // Don't cache.
          $this->renderer->addCacheableDependency($elements, new \stdClass());
          break;
      }
    }

    // Fallback value.
    if (!$entity && !$items->replace && !$value) {
      $value = $this->t('Placeholder for replacement title');
      // Don't cache.
      $this->renderer->addCacheableDependency($elements, new \stdClass());
    }

    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => $tag,
      '#attributes' => [
        'class' => [
          'heading',
        ],
      ],
      'content' => [
        '#type' => 'markup',
        '#markup' => $value,
        '#allowed_tags' => [
          'em',
          'br',
          'strong',
        ],
      ],
    ];

    return $elements;
  }

}
