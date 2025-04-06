<?php

namespace Drupal\rocketship_seo;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Schema.org builder for different Rocketship entities.
 */
interface RocketshipSchemaOrgGeneratorInterface {

  /**
   * Prepare html_head attachment with SEO data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Drupal content entity.
   *
   * @return array|null
   *   SEO data for html_head attachment.
   */
  public function build(ContentEntityInterface $entity): ?array;

}
