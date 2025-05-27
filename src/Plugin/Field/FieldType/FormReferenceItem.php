<?php

namespace Drupal\form_reference_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'form_reference' field type.
 *
 * @FieldType(
 *   id = "form_reference",
 *   label = @Translation("Form Reference"),
 *   description = @Translation("References a system or custom form."),
 *   default_widget = "form_reference_widget",
 *   default_formatter = "form_reference_formatter"
 * )
 */
class FormReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['form_id'] = DataDefinition::create('string')
      ->setLabel(('Form ID'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'form_id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('form_id')->getValue();
    return $value === NULL || $value === '';
  }
}
