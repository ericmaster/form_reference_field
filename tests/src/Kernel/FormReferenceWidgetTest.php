<?php

declare(strict_types=1);

namespace Drupal\Tests\form_reference_field\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests the FormReferenceWidget field widget.
 *
 * @group form_reference_field
 */
class FormReferenceWidgetTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'form_reference_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    // Use recommended approach for installing the 'sequences' table in Drupal 10+.
    if (method_exists($this, 'installSystemSchema')) {
      $this->installSystemSchema(['sequences']);
    } else {
      $this->installSchema('system', ['sequences']);
    }
  }

  /**
   * Test the widget renders and saves values correctly.
   */
  public function testFormReferenceWidget(): void {
    // Create a content type.
    $type = NodeType::create([
      'type' => 'formref',
      'name' => 'Form Reference',
    ]);
    $type->save();

    // Create field storage and field config for the form_reference field.
    FieldStorageConfig::create([
      'field_name' => 'field_form_ref',
      'entity_type' => 'node',
      'type' => 'form_reference',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_form_ref',
      'entity_type' => 'node',
      'bundle' => 'formref',
      'label' => 'Form Reference',
    ])->save();

    // Create a user.
    $user = User::create([
      'name' => 'testuser',
    ]);
    $user->save();

    // Create a node with the field.
    $node = Node::create([
      'type' => 'formref',
      'title' => 'Test Node',
      'uid' => $user->id(),
      'field_form_ref' => [
        [
          'form_id' => 'contact_message_feedback_form',
          'form_args' => [
            ['text' => 'foo'],
            ['text' => 'bar'],
          ],
        ],
      ],
    ]);
    $node->save();

    $this->assertSame('contact_message_feedback_form', $node->field_form_ref->form_id);
    $this->assertSame('foo', $node->field_form_ref->form_args[0]['text']);
    $this->assertSame('bar', $node->field_form_ref->form_args[1]['text']);
  }
}
