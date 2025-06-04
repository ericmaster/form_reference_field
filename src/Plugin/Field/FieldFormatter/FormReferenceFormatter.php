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
    $entity_form_builder = \Drupal::service('entity.form_builder');
    $form_discovery = \Drupal::service('form_reference_field.form_discovery');
    foreach ($items as $delta => $item) {
      if (empty($item->form_id)) {
        return [];
      }
      $form_id = $item->form_id;
      // Use the service to get the entity type ID for entity forms.
      $entity_type_id = $form_discovery->getEntityTypeIdFromEntityFormClass($form_id);
      if (empty($form)) {
        // If the form is not an entity form, we just use the form_id.
        // $form = $form_builder->getForm($form_id);
      }
      // dpm($form, 'Form for ' . $form_id);
      $elements[$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-reference-field']],
        // TODO: pass the parameters to the form if needed.
        // 'form' => $form,
        // 'form' => $form_builder->getForm($form_id),
      ];
    }
    return $elements;
  }
}
