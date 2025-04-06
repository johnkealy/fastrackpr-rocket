<?php

namespace Drupal\rocketship_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;

/**
 * Description replacement field.
 *
 * @FieldType(
 *   id = "contentblock_description_replacement",
 *   label = @Translation("Current Node Description replacement"),
 *   description = @Translation("Special field that grabs the current node and uses that description if this field is empty"),
 *   default_widget = "contentblock_description_replacement_widget",
 *   default_formatter = "contentblock_description_replacement_formatter",
 *   cardinality = 1
 * )
 */
class ContentBlockDescriptionReplacement extends TextLongItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['replace'] = DataDefinition::create('boolean')
      ->setLabel(t('Boolean value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema = parent::schema($field_definition);

    $schema['columns']['replace'] = [
      'type' => 'int',
      'size' => 'tiny',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    $values['replace'] = mt_rand(0, 1);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // If the checkbox wasn't checked, consider this field empty.
    $value = $this->get('replace')->getValue();
    return $value === NULL || $value === '' || $value === FALSE;
  }

}
