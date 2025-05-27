<?php

namespace Drupal\form_reference_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'form_reference_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "form_reference_formatter",
 *   label = @Translation("Form Reference Formatter"),
 *   field_types = {
 *     "form_reference"
 *   }
 * )
 */
class FormReferenceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $form_builder = \Drupal::service('form_builder');
    foreach ($items as $delta => $item) {
      if (!empty($item->form_id)) {
        $form_id = $item->form_id;
        $elements[$delta] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['form-reference-field']],
          'form' => $form_builder->getForm($form_id),
        ];
      }
    }
    return $elements;
  }
}
