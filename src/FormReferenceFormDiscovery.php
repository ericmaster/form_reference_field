<?php

namespace Drupal\form_reference_field;

use Symfony\Component\Finder\Finder;

/**
 * Service to discover available form classes in modules.
 */
class FormReferenceFormDiscovery
{

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  public function __construct($moduleHandler, $extensionListModule, $fileSystem) {
    $this->moduleHandler = $moduleHandler;
    $this->extensionListModule = $extensionListModule;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Returns a list of available forms (same as widget).
   */
  public function getFormOptions()
  {
    $options = [];
    foreach ($this->moduleHandler->getModuleList() as $module => $info) {
      $module_path = $this->extensionListModule->getPath($module);
      $absolute_path = $this->fileSystem->realpath($module_path . '/src/Form');
      $finder = new Finder();
      if (!is_dir($absolute_path)) {
        continue;
      }
      $finder->files()->in($absolute_path)->name('*Form.php');
      foreach ($finder as $file) {
        
        // Regex to match form classes in the standard Drupal module structure:
        // - Starts with 'modules/' (can be custom, contrib, or any group)
        // - Captures the module name in the first group
        // - Captures the form class name (without 'Form.php') in the second group
        // Example match: modules/custom/my_module/src/Form/MyCustomForm.php
        //   Group 1: my_module
        //   Group 2: MyCustom
        $pattern = '#modules/(?:custom|contrib|[^/]+/)?([^/]+)/src/Form/(.+)Form.php$#';
        if (preg_match($pattern, $file->getRealPath(), $matches)) {
          $module_name = $matches[1];
          $form_class = 'Drupal\\' . $module_name . '\\Form\\' . $matches[2] . 'Form';
          if (class_exists($form_class)) {
            $options[$form_class] = $form_class;
          }
          break;
        }
      }
    }
    $this->moduleHandler->invokeAll('form_reference_options_alter', [&$options]);
    return $options;
  }
}
