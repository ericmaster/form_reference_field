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
class FormReferenceWidget extends WidgetBase implements ContainerFactoryPluginInterface
{

  /**
   * @var \Drupal\form_reference_field\FormReferenceFormDiscovery
   */
  protected $formDiscovery;

  /**
   * Constructs a CustomWidget object.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $formDiscovery)
  {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->formDiscovery = $formDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {
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
          'callback' => [get_class($this), 'argumentTypeAjax'],
          'wrapper' => 'form-arg-type-wrapper-' . $delta . '-' . $i,
        ],
      ];
      $element['form_args'][$i]['#prefix'] = '<div id="form-arg-type-wrapper-' . $delta . '-' . $i . '">';
      $element['form_args'][$i]['#suffix'] = '</div>';
      if ($arg_type === 'entity') {
        $element['form_args'][$i]['entity'] = [
          '#type' => 'entity_autocomplete',
          '#target_type' => 'node', // TODO: make configurable.
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
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
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
   * Ajax callback for add more arguments.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state)
  {
    // Find the triggering element's parents to return the correct part of the form.
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents); // Remove the button itself.
    $element = \Drupal\Component\Utility\NestedArray::getValue($form, $parents);
    return $element;
  }

  /**
   * Submit handler for add more arguments.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state)
  {
    // No-op, state is handled in formElement.
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
      'allowed_forms' => [],
    ] + parent::defaultSettings();
  }

  /**
   * Helper to get allowed form options for this widget instance.
   */
  protected function getAllowedFormOptions()
  {
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
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
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
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
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
   * Ajax callback for switching argument type.
   */
  public static function argumentTypeAjax(array $form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents); // Remove the select itself.
    $element = \Drupal\Component\Utility\NestedArray::getValue($form, $parents);
    return $element;
  }
}
