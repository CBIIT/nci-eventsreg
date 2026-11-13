<?php

namespace Drupal\event_task_hub;

use Drupal\comment\Entity\CommentType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/** Idempotent provisioning, called on both first installation and update. */
final class Setup {

  public const BUNDLE = 'manage_my';
  public const COMMENT_TYPE = 'event_task_note';

  public static function tasks(): array {
    return json_decode(file_get_contents(dirname(__DIR__) . '/task_map.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  }

  public static function discussionField(string $task): string {
    return 'field_eth_' . substr($task, strlen('field_task_'));
  }

  public function provision(): void {
    if (!NodeType::load(self::BUNDLE)) {
      NodeType::create([
        'type' => self::BUNDLE, 'name' => 'Manage My Event',
        'description' => 'Event tasks, progress and task discussions.',
        'new_revision' => TRUE, 'preview_mode' => 1, 'display_submitted' => FALSE,
      ])->save();
    }
    $definitions = json_decode(file_get_contents(dirname(__DIR__) . '/field_definitions.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $form = EntityFormDisplay::load('node.manage_my.default')
      ?: EntityFormDisplay::create(['targetEntityType' => 'node', 'bundle' => self::BUNDLE, 'mode' => 'default', 'status' => TRUE]);
    $view = EntityViewDisplay::load('node.manage_my.default')
      ?: EntityViewDisplay::create(['targetEntityType' => 'node', 'bundle' => self::BUNDLE, 'mode' => 'default', 'status' => TRUE]);
    $form->setComponent('title', ['type' => 'string_textfield', 'weight' => -20]);
    $weight = 0;
    foreach ($definitions as $name => $definition) {
      $this->field('node', self::BUNDLE, $name, $definition['type'],
        $definition['storage_settings'], $definition['settings'], $definition['label'], $definition['cardinality']);
      $widget = match ($definition['type']) {
        'list_string' => 'options_select', 'datetime' => 'datetime_default',
        'email' => 'email_default', 'link' => 'link_default',
      };
      if (!$form->getComponent($name)) {
        $form->setComponent($name, ['type' => $widget, 'weight' => ++$weight]);
      }
      if (isset(self::tasks()[$name])) {
        $view->setComponent($name, ['type' => 'list_default', 'label' => 'above', 'weight' => $weight]);
      }
      else {
        $view->removeComponent($name);
      }
    }
    if (!CommentType::load(self::COMMENT_TYPE)) {
      CommentType::create([
        'id' => self::COMMENT_TYPE, 'label' => 'Event task comment',
        'target_entity_type_id' => 'node',
        'description' => 'A comment on one event task.',
      ])->save();
    }
    foreach (self::tasks() as $task => $label) {
      $name = self::discussionField($task);
      $this->field('node', self::BUNDLE, $name, 'comment',
        ['comment_type' => self::COMMENT_TYPE],
        ['default_mode' => 0, 'per_page' => 10, 'anonymous' => 0,
          'form_location' => 0, 'preview' => 0],
        $label . ' comments', 1, FALSE, [['status' => 2]]);
      $form->removeComponent($name);
      $view->removeComponent($name);
    }
    // Retain old fields/data, but stop displaying the superseded Webform.
    foreach (['field_post_an_update', 'field_task_total_submissions'] as $legacy) {
      $form->removeComponent($legacy);
      $view->removeComponent($legacy);
    }
    $form->save();
    $view->save();
    $this->field('comment', self::COMMENT_TYPE, 'field_eth_message', 'string_long', [], [], 'Comment', 1, TRUE);
    $this->field('comment', self::COMMENT_TYPE, 'field_eth_files', 'file',
      ['uri_scheme' => 'private', 'display_field' => FALSE, 'display_default' => TRUE],
      ['file_directory' => 'event_task_hub/[date:custom:Y]/[date:custom:m]',
        'file_extensions' => 'ics gif jpg jpeg png bmp tif tiff txt rtf odf pdf doc docx ppt pptx xls xlsx zip',
        'max_filesize' => '50 MB', 'description_field' => TRUE],
      'Attachments', -1);
    $comment_form = EntityFormDisplay::load('comment.event_task_note.default')
      ?: EntityFormDisplay::create(['targetEntityType' => 'comment', 'bundle' => self::COMMENT_TYPE, 'mode' => 'default', 'status' => TRUE]);
    $comment_form->removeComponent('subject');
    $comment_form->setComponent('field_eth_message', ['type' => 'string_textarea', 'weight' => 0, 'settings' => ['rows' => 5]]);
    $comment_form->setComponent('field_eth_files', ['type' => 'file_generic', 'weight' => 1]);
    $comment_form->save();
    $comment_view = EntityViewDisplay::load('comment.event_task_note.default')
      ?: EntityViewDisplay::create(['targetEntityType' => 'comment', 'bundle' => self::COMMENT_TYPE, 'mode' => 'default', 'status' => TRUE]);
    $comment_view->setComponent('field_eth_message', ['type' => 'basic_string', 'label' => 'hidden', 'weight' => 0]);
    $comment_view->setComponent('field_eth_files', ['type' => 'file_default', 'label' => 'above', 'weight' => 1]);
    $comment_view->save();
    $permissions = ['access content', 'access event task discussions', 'access comments', 'post comments', 'skip comment approval'];
    if (!Role::load('event_task_participant')) {
      Role::create(['id' => 'event_task_participant', 'label' => 'Event task participant', 'permissions' => $permissions])->save();
    }
    if (!Role::load('event_task_manager')) {
      Role::create(['id' => 'event_task_manager', 'label' => 'Event task manager', 'permissions' => array_merge($permissions, [
        'create manage_my content', 'edit any manage_my content', 'view own unpublished content',
      ])])->save();
    }
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  private function field(string $entity_type, string $bundle, string $name, string $type, array $storage_settings, array $settings, string $label, int $cardinality = 1, bool $required = FALSE, array $default = []): void {
    $storage = FieldStorageConfig::loadByName($entity_type, $name);
    if ($storage && ($storage->getType() !== $type || ($type === 'comment' && $storage->getSetting('comment_type') !== self::COMMENT_TYPE))) {
      throw new \RuntimeException('Conflicting field storage: ' . $entity_type . '.' . $name);
    }
    if ($type === 'file' && $storage && $storage->getSetting('uri_scheme') !== 'private') {
      throw new \RuntimeException('Task attachments must use private storage.');
    }
    if (!$storage) {
      $storage = FieldStorageConfig::create([
        'entity_type' => $entity_type, 'field_name' => $name, 'type' => $type,
        'cardinality' => $cardinality, 'settings' => $storage_settings,
      ]);
      $storage->save();
    }
    if (!FieldConfig::loadByName($entity_type, $bundle, $name)) {
      FieldConfig::create([
        'entity_type' => $entity_type, 'bundle' => $bundle, 'field_name' => $name,
        'label' => $label, 'required' => $required, 'settings' => $settings,
        'default_value' => $default,
      ])->save();
    }
  }

}
