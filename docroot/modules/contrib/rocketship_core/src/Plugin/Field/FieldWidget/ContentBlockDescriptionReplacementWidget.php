<?php

namespace Drupal\rocketship_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;

/**
 * Plugin implementation of the 'contentblock_description_replacement_widget' widget.
 *
 * @FieldWidget(
 *   id = "contentblock_description_replacement_widget",
 *   label = @Translation("Description Replacement Widget"),
 *   field_types = {
 *     "contentblock_description_replacement"
 *   }
 * )
 */
class ContentBlockDescriptionReplacementWidget extends TextareaWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'checkbox_title' => 'Replace the description',
      'checkbox_description' => "Replace the description on the detail page for this piece of content with a different description, which can include the following html: &lt;em&gt;&lt;/em&gt; and &lt;strong&gt;&lt;/strong&gt; Leave this unchecked to use the description of this piece of content as is.",
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['checkbox_title'] = [
      '#type' => 'textfield',
      '#title' => t('Checkbox title'),
      '#default_value' => $this->getSetting('checkbox_title'),
      '#required' => TRUE,
    ];
    $element['checkbox_description'] = [
      '#type' => 'textarea',
      '#title' => t('Checkbox description'),
      '#default_value' => $this->getSetting('checkbox_description'),
      '#description' => t('Text that will be shown below the checkbox to indicate what checking it will do and what you can use the replacement description field for. Escape the tags yourself as needed.'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $checkbox_title = $this->getSetting('checkbox_title');
    if (!empty($checkbox_title)) {
      $summary[] = t('Checkbox title: @title', ['@title' => $checkbox_title]);
    }
    $description = $this->getSetting('checkbox_description');
    if (!empty($description)) {
      $summary[] = t('Checkbox description: @description', [
        '@description' => substr($description, 0, 50) . '...',
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // We put the element in a separate object, otherwise the states will cause
    // issues.
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value'] = $main_widget;

    // Create a unique selector to use with #states.
    $selector = "{$items->getEntity()->getEntityTypeId()}_{$items->getFieldDefinition()->getName()}_delta_{$delta}";
    // Apply #states.
    $element['value']['#states'] = [
      'visible' => [
        ':input[data-state-selector="' . $selector . '"]' => ['checked' => TRUE],
      ],
    ];

    $element['replace'] = [
      '#title' => t($this->getSetting('checkbox_title')),
      '#description' => t($this->getSetting('checkbox_description')),
      '#type' => 'checkbox',
      '#default_value' => !empty($items[$delta]->replace),
      // Add our unique selector as data attribute.
      '#attributes' => [
        'data-state-selector' => $selector,
      ],
      '#weight' => -50,
    ];

    // Add our validate function.
    if (!isset($element['#element_validate'])) {
      $element['#element_validate'] = [];
    }

    $element['#element_validate'][] = [$this, 'validate'];

    return $element;
  }

  /**
   * Validate the entire element.
   */
  public function validate($element, FormStateInterface $form_state) {
    if (!empty($element['replace']['#value']) && empty($element['value']['value']['#value'])) {
      // If replace description was checked, then the text field becomes required.
      $form_state->setError($element, t('The %field field is required.', ['%field' => $element['value']['value']['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    // We only need the values in 'value'.
    foreach ($values as $value) {
      $value['value']['replace'] = $value['replace'];
      $new_values[] = $value['value'];
    }
    return parent::massageFormValues($new_values, $form, $form_state);
  }

}
