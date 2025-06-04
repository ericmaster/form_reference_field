<?php

namespace Drupal\form_reference_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'form_reference_widget' widget.
 *
 * @FieldWidget(
 *   id = "form_reference_widget",
 *   label = @Translation("Form Reference Widget"),
 *   field_types = {
 *     "form_reference"
 *   }
 * )
 */
class FormReferenceWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\form_reference_field\FormReferenceFormDiscovery
   */
  protected $formDiscovery;

  /**
   * Constructs a CustomWidget object.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $formDiscovery) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->formDiscovery = $formDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('form_reference_field.form_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->form_id) ? $items[$delta]->form_id : '';
    $options = $this->getAllowedFormOptions();
    $element['form_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Form'),
      '#options' => $options,
      '#default_value' => $value,
      '#empty_option' => $this->t('- Select -'),
      '#required' => $element['#required'],
    ];
    // Add a multiple value field for arguments with add more functionality.
    // Get form_args from form_state if available, otherwise from $items.
    $form_args = NULL;
    // Try to get from form_state (handles both AJAX and normal submissions).
    $parents = $element['#field_parents'] ?? [];
    $parents = array_merge($parents, [$items->getName(), $delta, 'form_args']);
    $form_args = $form_state->getValue($parents);
    if ($form_args === NULL) {
      $form_args = isset($items[$delta]->form_args) && is_array($items[$delta]->form_args) ? $items[$delta]->form_args : [''];
    }
    $element['form_args'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form arguments (tokens allowed)'),
      '#description' => $this->t('Enter one or more arguments to pass to the form. Use tokens if needed.'),
      '#tree' => TRUE,
    ];
    // Add token browser if token module is available.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['form_args']['token_browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node', 'user', 'file', 'comment', 'taxonomy_term', 'block'],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
        '#weight' => 100,
      ];
    }
    $max_args = $form_state->get(['form_reference_field', 'max_args', $delta]) ?? count($form_args);
    if ($form_state->getTriggeringElement() && $form_state->getTriggeringElement()['#name'] === 'add_form_arg_' . $delta) {
      $max_args++;
      $form_state->set(['form_reference_field', 'max_args', $delta], $max_args);
    }
    // Get the configured entity type for entity reference arguments.
    $entity_target_type = $this->getSetting('entity_reference_target_type') ?: 'node';
    // Get all entity types for the per-argument select.
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $entity_type_options = [];
    foreach ($entity_types as $id => $definition) {
      if ($definition->get('entity_keys')['id'] ?? FALSE) {
        $entity_type_options[$id] = $definition->getLabel() ?: $id;
      }
    }
    $entity_target_type_default = $this->getSetting('entity_reference_target_type') ?: 'node';
    for ($i = 0; $i < $max_args; $i++) {
      $arg_type = isset($form_args[$i]['type']) ? $form_args[$i]['type'] : 'text';
      $element['form_args'][$i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-argument-wrapper']],
      ];
      $element['form_args'][$i]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Argument @num type', ['@num' => $i + 1]),
        '#options' => [
          'text' => $this->t('Text/Token'),
          'entity' => $this->t('Entity Reference'),
        ],
        '#default_value' => $arg_type,
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxCallback'],
          'wrapper' => 'form-arg-type-wrapper-' . $delta . '-' . $i,
        ],
      ];
      $element['form_args'][$i]['#prefix'] = '<div id="form-arg-type-wrapper-' . $delta . '-' . $i . '">';
      $element['form_args'][$i]['#suffix'] = '</div>';
      if ($arg_type === 'entity') {
        // Per-argument entity type select.
        $selected_entity_type = isset($form_args[$i]['entity_target_type']) ? $form_args[$i]['entity_target_type'] : $entity_target_type_default;
        $element['form_args'][$i]['entity_target_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Entity type for argument @num', ['@num' => $i + 1]),
          '#options' => $entity_type_options,
          '#default_value' => $selected_entity_type,
          '#required' => TRUE,
        ];
        $element['form_args'][$i]['entity'] = [
          '#type' => 'entity_autocomplete',
          '#target_type' => $selected_entity_type,
          '#title' => $this->t('Entity for argument @num', ['@num' => $i + 1]),
          '#default_value' => isset($form_args[$i]['entity']) ? $form_args[$i]['entity'] : NULL,
          '#required' => FALSE,
        ];
      } else {
        $element['form_args'][$i]['text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Argument @num', ['@num' => $i + 1]),
          '#default_value' => isset($form_args[$i]['text']) ? $form_args[$i]['text'] : (is_string($form_args[$i]) ? $form_args[$i] : ''),
          '#required' => FALSE,
        ];
      }
    }
    $element['form_args']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another argument'),
      '#name' => 'add_form_arg_' . $delta,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxCallback'],
        'wrapper' => 'form-args-wrapper-' . $delta,
      ],
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['add-more-button']],
    ];
    $element['form_args']['#prefix'] = '<div id="form-args-wrapper-' . $delta . '">';
    $element['form_args']['#suffix'] = '</div>';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allowed_forms' => [],
      'entity_reference_target_type' => 'node',
    ] + parent::defaultSettings();
  }

  /**
   * Helper to get allowed form options for this widget instance.
   */
  protected function getAllowedFormOptions() {
    $options = $this->formDiscovery->getFormOptions();
    $allowed_forms_setting = $this->getSetting('allowed_forms');
    if (is_string($allowed_forms_setting)) {
      $allowed = array_map('trim', explode(',', $allowed_forms_setting));
    } else {
      $allowed = array_filter((array) $allowed_forms_setting);
    }
    if (!empty($allowed)) {
      $options = array_intersect_key($options, array_flip($allowed));
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $allowed_forms_setting = $this->getSetting('allowed_forms');
    $elements['allowed_forms'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed forms'),
      '#tags' => TRUE,
      '#default_value' => $allowed_forms_setting,
      '#description' => $this->t('Select which forms can be referenced by this widget.'),
      '#autocomplete_route_name' => 'form_reference_field.autocomplete',
      '#autocomplete_route_parameters' => [],
    ];
    // Add entity reference target type setting.
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $entity_type_options = [];
    foreach ($entity_types as $id => $definition) {
      if ($definition->get('entity_keys')['id'] ?? FALSE) {
        $entity_type_options[$id] = $definition->getLabel() ?: $id;
      }
    }
    $elements['entity_reference_target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity reference target type'),
      '#options' => $entity_type_options,
      '#default_value' => $this->getSetting('entity_reference_target_type') ?: 'node',
      '#description' => $this->t('Select the entity type to use for entity reference arguments.'),
      '#required' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $allowed_form_options = $this->getAllowedFormOptions();
    if (empty($allowed_form_options)) {
      $summary[] = $this->t('Allowed forms: All');
    } else {
      $summary[] = $this->t(
        '<strong>Allowed forms:</strong><ul><li>' . implode('</li><li>', $allowed_form_options) . '</li></ul>',
      );
    }
    return $summary;
  }

  /**
   * AJAX callback for argument type switching and add more.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents); // Remove the triggering element itself.
    $element = \Drupal\Component\Utility\NestedArray::getValue($form, $parents);
    return $element;
  }
}
