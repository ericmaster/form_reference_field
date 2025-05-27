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
    return $element;
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
}
