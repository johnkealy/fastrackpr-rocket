<?php

namespace Drupal\rocketship_seo;

use Drupal\block_content\BlockContentInterface;

/**
 * Schema.org builder for different Rocketship entities.
 */
class RocketshipSchemaOrgGenerator implements RocketshipSchemaOrgGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function build($entity): ?array {
    $html_head = NULL;

    // Determine different methods for different entity classes.
    if ($entity instanceof BlockContentInterface) {
      $html_head = $this->buildBlock($entity);
    }

    return $html_head;
  }

  /**
   * Prepare html_head attachment with SEO data for blocks.
   *
   * @param \Drupal\block_content\BlockContentInterface $entity
   *   Block entity.
   *
   * @return array|null
   *   SEO data for html_head attachment.
   */
  protected function buildBlock(BlockContentInterface $entity): ?array {
    $html_head = NULL;

    // Determine different methods for different entity types.
    switch ($entity->bundle()) {
      case 'cb_faq':
        $json_ld = $this->buildBlockFaq($entity);
        break;
    }

    if (isset($json_ld)) {
      // Prepare script tag.
      $structured_data = [
        '#tag' => 'script',
        '#attributes' => ['type' => 'application/ld+json'],
        '#value' => json_encode($json_ld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
      ];

      // Prepare script tag.
      $html_head = [
        $structured_data,
        'rocketship_core_' . $entity->getEntityTypeId() . '_' . $entity->bundle() . '_json_ld',
      ];
    }

    return $html_head;
  }

  /**
   * Prepare ld+json SEO data for block FAQ.
   *
   * @param \Drupal\block_content\BlockContentInterface $entity
   *   Block entity.
   *
   * @return array|null
   *   SEO data for html_head attachment.
   */
  protected function buildBlockFaq(BlockContentInterface $entity): ?array {
    // Check if required data exists.
    if (!$entity->hasField('field_cb_faq_item') || $entity->get('field_cb_faq_item')->isEmpty()) {
      return NULL;
    }

    $json_ld = [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => [],
    ];

    $question_template = [
      '@type' => 'Question',
      'name' => '',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => '',
      ],
    ];

    foreach ($entity->get('field_cb_faq_item') as $item) {
      $question_template['name'] = trim(strip_tags($item->title));
      $question_template['acceptedAnswer']['text'] = trim(strip_tags($item->value));
      $json_ld['mainEntity'][] = $question_template;
    }

    return $json_ld;
  }

}
