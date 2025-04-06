<?php

namespace Drupal\field_limiter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field_formatter\Plugin\Field\FieldFormatter\FieldWrapperBase;

/**
 * Plugin implementation of the 'field_limiter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_limiter",
 *   label = @Translation("Limit the number of rendered items"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldLimiter extends FieldWrapperBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['limit'] = 0;
    $settings['offset'] = 0;
    return $settings;
  }

  /**
   * Returns the cardinality setting of the field instance.
   */
  protected function getCardinality() {
    if ($this->fieldDefinition instanceof FieldDefinitionInterface) {
      return $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    if ($this->getCardinality() == 1) {
      return [];
    }

    $form = parent::settingsForm($form, $form_state);

    $form['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Skip items'),
      '#default_value' => $this->getSetting('offset'),
      '#required' => TRUE,
      '#min' => 0,
      '#description' => $this->t('Number of items to skip from the beginning.'),
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Display items'),
      '#default_value' => $this->getSetting('limit'),
      '#required' => TRUE,
      '#min' => 0,
      '#description' => $this->t('Number of items to display. Set to 0 to display all items.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $offset = $this->getSetting('offset') + 1;
    $limit = $this->getSetting('limit');

    if ($limit == 0) {
      $summary[] = $this->t('Showing all values, starting at @offset.', [
        '@offset' => $offset,
      ]);
    }
    else {
      $summary[] = $this->formatPlural($limit, 'Limited to 1 value, starting at @offset.', 'Limited to @count values, starting at @offset.', [
        '@offset' => $offset,
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $field_values = $items->getValue();

    $offset = $this->getSetting('offset');
    // To show all elements, expressed by the setting '0', array_slice needs
    // NULL as 3rd argument.
    $limit = $this->getSetting('limit') == 0 ? NULL : $this->getSetting('limit');

    if ($limit === NULL) {
      // Let array_slice limit the field values to the ones we want to keep.
      $limited_values = array_slice($field_values, $offset, $limit);
      $items->setValue($limited_values);

      // Generate the output of the field.
      $field_output = $this->getFieldOutput($items, $langcode);
      $children = Element::children($field_output);
    }
    else {
      // Iterate over the $field_values to get $limit items.
      $iteration_limit = $limit;
      $iteration_offset = $offset;
      $total_values = count($field_values);
      $field_output = [];

      do {
        // Let array_slice limit the field values to the ones we want to keep.
        $limited_values = array_slice($field_values, $iteration_offset, $iteration_limit);
        $items->setValue($limited_values);

        // Generate the output of the field.
        $field_output = array_merge($field_output, $this->getFieldOutput($items, $langcode));

        // Add the rendered items to the children array.
        $children = Element::children($field_output);

        // Determine the next iteration parameters.
        $iteration_offset = $iteration_offset + $iteration_limit;
        $iteration_limit = $limit - count($children);

        // Adjust the iteration limit to the remaining items when necessary.
        if ($total_values - $iteration_offset < $iteration_limit) {
          $iteration_limit = $total_values - $iteration_offset;
        }

      } while ($iteration_limit > 0);
    }

    // Take the element children from the field output and return them.
    return array_intersect_key($field_output, array_flip($children));
  }

}
