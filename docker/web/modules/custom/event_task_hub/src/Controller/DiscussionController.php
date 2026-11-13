<?php

namespace Drupal\event_task_hub\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\event_task_hub\Setup;
use Drupal\node\NodeInterface;

/** Builds the native comment form for one server-selected task. */
final class DiscussionController extends ControllerBase {

  public function postAccess(NodeInterface $node, string $task, AccountInterface $account): AccessResultInterface {
    $field = Setup::discussionField($task);
    $valid = $node->bundle() === Setup::BUNDLE && isset(Setup::tasks()[$task])
      && $node->hasField($task) && !$node->get($task)->isEmpty()
      && $node->hasField($field) && (int) $node->get($field)->status === 2;
    $access = AccessResult::allowedIf($valid && $account->isAuthenticated())
      ->andIf(AccessResult::allowedIfHasPermissions($account, ['access event task discussions', 'access comments', 'post comments']))
      ->andIf($node->access('view', $account, TRUE))->addCacheableDependency($node)->cachePerUser();
    if ($valid) {
      $access = $access->andIf($node->get($task)->access('view', $account, TRUE))
        ->andIf($node->get($field)->access('edit', $account, TRUE));
    }
    return $access;
  }

  public function selectableTasks(NodeInterface $node, AccountInterface $account): array {
    $tasks = [];
    foreach (array_keys(Setup::tasks()) as $task) {
      if ($this->postAccess($node, $task, $account)->isAllowed()) {
        $tasks[$task] = $node->getFieldDefinition($task)->getLabel();
      }
    }
    return $tasks;
  }

  public function updateAccess(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf((bool) $this->selectableTasks($node, $account))
      ->cachePerUser()->addCacheableDependency($node);
  }

  public function update(NodeInterface $node): array {
    $tasks = $this->selectableTasks($node, $this->currentUser());
    if (!$tasks) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }
    $first = array_key_first($tasks);
    $comment = Comment::create([
      'comment_type' => Setup::COMMENT_TYPE, 'entity_type' => 'node',
      'entity_id' => $node->id(), 'field_name' => Setup::discussionField($first),
      'uid' => $this->currentUser()->id(),
    ]);
    return \Drupal::service('entity.form_builder')->getForm($comment, 'event_task_update');
  }

  public function post(NodeInterface $node, string $task): array {
    $comment = Comment::create([
      'comment_type' => Setup::COMMENT_TYPE, 'entity_type' => 'node',
      'entity_id' => $node->id(), 'field_name' => Setup::discussionField($task),
      'uid' => $this->currentUser()->id(),
    ]);
    $form = \Drupal::service('entity.form_builder')->getForm($comment);
    // Core's default form action uses comment.reply. Keep the task-specific route.
    $form['#action'] = \Drupal\Core\Url::fromRoute('event_task_hub.post', ['node' => $node->id(), 'task' => $task])->toString();
    return [
      'context' => ['#plain_text' => $node->label() . ' — ' . $node->getFieldDefinition($task)->getLabel()],
      'form' => $form,
      'back' => ['#type' => 'link', '#title' => $this->t('Back to event'), '#url' => $node->toUrl()],
      '#cache' => ['max-age' => 0],
    ];
  }

}
