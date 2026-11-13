<?php

namespace Drupal\Tests\event_task_hub\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\Core\File\FileSystemInterface;
use Drupal\event_task_hub\Setup;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Covers provisioning, task isolation, posting, notifications and file access.
 *
 * @group event_task_hub
 */
final class EventTaskHubTest extends BrowserTestBase {

  protected static $modules = ['event_task_hub'];
  protected $defaultTheme = 'stark';

  protected function participant() {
    return $this->drupalCreateUser([
      'access content', 'access event task discussions', 'access comments',
      'post comments', 'skip comment approval',
    ]);
  }

  protected function event(string $title) {
    $node = Node::create([
      'type' => Setup::BUNDLE, 'title' => $title, 'status' => 1,
      'field_task_overview' => 'content_needed',
      'field_task_agenda' => 'completed',
    ]);
    $node->save();
    return $node;
  }

  public function testFreshInstallAndIdempotentProvisioning(): void {
    $this->assertNotNull(NodeType::load('manage_my'));
    $node = $this->event('Keep this event');
    (new Setup())->provision();
    $this->assertSame('Keep this event', Node::load($node->id())->label());
    foreach (array_keys(Setup::tasks()) as $task) {
      $this->assertNotNull(FieldConfig::loadByName('node', 'manage_my', $task));
      $this->assertNotNull(FieldConfig::loadByName('node', 'manage_my', Setup::discussionField($task)));
    }
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('webform'));
    $this->drupalLogin($this->participant());
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('Task Progress');
    $this->assertSession()->elementsCount('css', '.eth-task', 2);
    $this->assertSession()->elementExists('css', '.eth-discussion:not([open])');
  }

  public function testTaskAndEventIsolation(): void {
    $user = $this->participant();
    $a = $this->event('Event A');
    $b = $this->event('Event B');
    foreach ([[$a, 'field_task_overview', 'Only A overview'], [$a, 'field_task_agenda', 'Only A agenda'], [$b, 'field_task_overview', 'Only B overview']] as [$node, $task, $text]) {
      Comment::create([
        'comment_type' => Setup::COMMENT_TYPE, 'entity_type' => 'node',
        'entity_id' => $node->id(), 'field_name' => Setup::discussionField($task),
        'uid' => $user->id(), 'status' => 1, 'field_eth_message' => $text,
      ])->save();
    }
    $this->drupalLogin($user);
    $this->drupalGet($a->toUrl());
    $overview = $this->assertSession()->elementExists('css', '#eth-field_task_overview');
    $this->assertStringContainsString('Only A overview', $overview->getText());
    $this->assertStringNotContainsString('Only A agenda', $overview->getText());
    $this->assertSession()->pageTextNotContains('Only B overview');
    $this->drupalLogout();
    $this->drupalGet($a->toUrl());
    $this->assertSession()->pageTextNotContains('Only A overview');
    $this->drupalGet('/node/' . $a->id() . '/task-comment/field_task_overview');
    $this->assertSession()->statusCodeEquals(403);
  }

  public function testNativePostingAndEmail(): void {
    $user = $this->participant();
    $node = $this->event('Notification test');
    $node->set('field_task_notify_emails', [['value' => 'notify@example.com']])->save();
    $this->drupalLogin($user);
    $this->drupalGet('/node/' . $node->id() . '/task-comment/field_task_overview');
    $this->submitForm(['field_eth_message[0][value]' => '<script>Escaped comment</script>'], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('<script>Escaped comment</script>');
    $this->assertSession()->elementNotExists('css', '.eth-message script');
    $ids = \Drupal::entityTypeManager()->getStorage('comment')->getQuery()->accessCheck(FALSE)
      ->condition('comment_type', Setup::COMMENT_TYPE)->execute();
    $this->assertCount(1, $ids);
    $comment = Comment::load(reset($ids));
    $this->assertSame(Setup::discussionField('field_task_overview'), $comment->getFieldName());
    $mails = $this->getMails(['id' => 'event_task_hub_new_comment']);
    $this->assertCount(1, $mails);
    $this->assertSame('notify@example.com', $mails[0]['to']);
    // Editing an already-published comment must not send another notification.
    $comment->set('field_eth_message', 'Edited')->save();
    $this->assertCount(1, $this->getMails(['id' => 'event_task_hub_new_comment']));
  }

  public function testInlineUpdateAndFilteredHistory(): void {
    $user = $this->participant();
    $node = $this->event('Inline form test');
    $this->drupalLogin($user);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementNotExists('css', '.eth-post-card');
    $this->assertSession()->pageTextContains('Event Update History');
    $this->drupalGet('/node/' . $node->id() . '/event-update');
    $this->submitForm([
      'related_to_task' => 'field_task_agenda',
      'field_eth_message[0][value]' => 'Agenda update from inline form',
    ], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $ids = \Drupal::entityTypeManager()->getStorage('comment')->getQuery()->accessCheck(FALSE)
      ->condition('comment_type', Setup::COMMENT_TYPE)->execute();
    $this->assertCount(1, $ids);
    $comment = Comment::load(reset($ids));
    $this->assertSame(Setup::discussionField('field_task_agenda'), $comment->getFieldName());
    $this->drupalGet($node->toUrl(), ['query' => ['eth_task' => 'field_task_overview']]);
    $history = $this->assertSession()->elementExists('css', '#eth-update-history');
    $this->assertStringContainsString('No updates yet.', $history->getText());
    $this->drupalGet($node->toUrl(), ['query' => ['eth_task' => 'field_task_agenda']]);
    $history = $this->assertSession()->elementExists('css', '#eth-update-history');
    $this->assertStringContainsString('Agenda update from inline form', $history->getText());
  }

  public function testPrivateAttachmentAccessAndUsage(): void {
    $user = $this->participant();
    $node = $this->event('Private attachment');
    $directory = 'private://event_task_hub';
    $this->container->get('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    file_put_contents($directory . '/test.txt', 'Private task file');
    $file = File::create(['uri' => $directory . '/test.txt', 'filename' => 'test.txt', 'uid' => $user->id()]);
    $file->save();
    $comment = Comment::create([
      'comment_type' => Setup::COMMENT_TYPE, 'entity_type' => 'node',
      'entity_id' => $node->id(), 'field_name' => Setup::discussionField('field_task_overview'),
      'uid' => $user->id(), 'status' => 1, 'field_eth_message' => 'See attachment',
      'field_eth_files' => [['target_id' => $file->id()]],
    ]);
    $comment->save();
    $this->assertNotEmpty($this->container->get('file.usage')->listUsage($file));
    $url = $this->container->get('file_url_generator')->generateAbsoluteString($file->getFileUri());
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($user);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $node->setUnpublished()->save();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);
  }

}
