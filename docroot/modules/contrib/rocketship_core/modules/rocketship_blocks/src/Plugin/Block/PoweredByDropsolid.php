<?php

namespace Drupal\rocketship_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PoweredByDropsolid' block.
 *
 * @Block(
 *  id = "powered_by_dropsolid",
 *  admin_label = @Translation("Powered by Dropsolid"),
 * )
 */
class PoweredByDropsolid extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['powered_by_dropsolid'] = [
      '#markup' => $this->t('Powered by Dropsolid'),
    ]; 

    return $build;
  }

}
