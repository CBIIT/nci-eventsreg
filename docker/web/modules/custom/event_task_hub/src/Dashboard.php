<?php

namespace Drupal\event_task_hub;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/** Server-rendered task progress and permission-checked native discussions. */
final class Dashboard {

  public function build(NodeInterface $node): array {
    $account = \Drupal::currentUser();
    $help = json_decode(file_get_contents(dirname(__DIR__) . '/task_help.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $tasks = [];
    $counts = [];
    $done = 0;
    foreach (Setup::tasks() as $task => $legacy_label) {
      if (!$node->hasField($task) || $node->get($task)->isEmpty() || !$node->get($task)->access('view', $account)) {
        continue;
      }
      $key = (string) $node->get($task)->value;
      $definition = $node->getFieldDefinition($task);
      $allowed = $definition->getFieldStorageDefinition()->getSetting('allowed_values');
      $status = $allowed[$key] ?? $key;
      $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower(str_replace('_', '-', $key)));
      $counts[$slug]['count'] = ($counts[$slug]['count'] ?? 0) + 1;
      $counts[$slug]['label'] = $status;
      $done += in_array($key, ['completed', 'approved'], TRUE) ? 1 : 0;
      $row = [
        '#theme' => 'event_task_hub_task', '#label' => $definition->getLabel(),
        '#status' => $status, '#status_key' => $slug,
        '#anchor' => 'eth-' . $task, '#help' => $this->help($help[$task] ?? []),
      ];
      if ($task === 'field_task_speaker_bio_web_form' && $node->hasField('field_task_speaker_bio_link')
          && $node->get('field_task_speaker_bio_link')->access('view', $account)) {
        $row['#task_link'] = $node->get('field_task_speaker_bio_link')->view(['type' => 'link', 'label' => 'hidden']);
      }
      $field = Setup::discussionField($task);
      if ($node->hasField($field) && $account->isAuthenticated()
          && $account->hasPermission('access event task discussions')
          && $account->hasPermission('access comments')
          && $node->get($field)->access('view', $account)) {
        $row['#discussion'] = $this->discussion($node, $task, $field, array_search($task, array_keys(Setup::tasks()), TRUE));
      }
      $tasks[] = $row;
    }
    $total = count($tasks);
    $order = array_flip(['content-needed', 'email-needed', 'apply-for-approval', 'approval-needed', 'approval-pending', 'testing-required', 'in-development', 'approved', 'completed']);
    uksort($counts, static fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));
    $summary = [
      '#theme' => 'event_task_hub_dashboard', '#tasks' => $tasks,
      '#counts' => $counts, '#total' => $total, '#done' => $done,
      '#percent' => $total ? (int) round(100 * $done / $total) : 0,
      '#launch' => $this->launch($node),
      '#attached' => ['library' => ['event_task_hub/tasks']],
      '#cache' => ['max-age' => 0, 'contexts' => ['user', 'url.query_args']],
    ];
    $page = ['summary' => $summary, '#cache' => ['max-age' => 0, 'contexts' => ['user', 'url.query_args']]];
    $controller = \Drupal::service('class_resolver')->getInstanceFromDefinition(\Drupal\event_task_hub\Controller\DiscussionController::class);
    if ($controller->updateAccess($node, $account)->isAllowed()) {
      $page['post'] = [
        '#theme' => 'event_task_hub_post',
        '#form' => $controller->update($node),
      ];
      $page['post']['#form']['#attributes']['class'][] = 'eth-update-form';
    }
    if ($account->isAuthenticated() && $account->hasPermission('access event task discussions') && $account->hasPermission('access comments')) {
      $page['history'] = $this->history($node);
    }
    return $page;
  }

  private function discussion(NodeInterface $node, string $task, string $field, int $index): array {
    if ((int) $node->get($field)->status === 0) {
      return [];
    }
    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    // EntityQuery does not enforce per-parent node grants for comments. We have
    // checked the parent and task field above; check each loaded comment below.
    $query = $storage->getQuery()->accessCheck(FALSE)
      ->condition('comment_type', Setup::COMMENT_TYPE)
      ->condition('entity_type', 'node')->condition('entity_id', $node->id())
      ->condition('field_name', $field)->condition('status', 1);
    $total = (int) (clone $query)->count()->execute();
    $pager_id = $index + 1;
    $pager = \Drupal::service('pager.manager')->createPager($total, 10, $pager_id);
    $ids = $query->sort('created', 'DESC')->sort('cid', 'DESC')
      ->range($pager->getCurrentPage() * 10, 10)->execute();
    $build = [
      '#type' => 'details', '#title' => t('Comments (@count)', ['@count' => $total]),
      '#open' => FALSE, '#attributes' => ['class' => ['eth-discussion']],
      '#cache' => ['max-age' => 0],
    ];
    $build['items'] = [];
    foreach ($storage->loadMultiple($ids) as $comment) {
      if (!$comment->access('view')) {
        continue;
      }
      $item = $this->commentItem($comment);
      $build['items']['comment_' . $comment->id()] = $item;
    }
    if (!$total) {
      $build['empty'] = ['#type' => 'html_tag', '#tag' => 'p', '#value' => t('No comments yet.')];
    }
    $build['pager'] = ['#type' => 'pager', '#element' => $pager_id, '#parameters' => [], '#route_name' => '<current>'];
    $url = Url::fromRoute('event_task_hub.post', ['node' => $node->id(), 'task' => $task]);
    if ($url->access()) {
      $build['post'] = ['#type' => 'link', '#title' => t('Add a comment or attachment'), '#url' => $url, '#attributes' => ['class' => ['eth-post']]];
    }
    elseif ((int) $node->get($field)->status === 1) {
      $build['closed'] = ['#plain_text' => t('This discussion is closed.')];
    }
    return $build;
  }

  private function commentItem(\Drupal\comment\CommentInterface $comment, bool $with_task = FALSE): array {
    $item = [
      '#theme' => 'event_task_hub_comment', '#comment_id' => $comment->id(),
      '#author' => $comment->getAuthorName(),
      '#date' => \Drupal::service('date.formatter')->format($comment->getCreatedTime(), 'medium'),
      '#message' => $comment->get('field_eth_message')->access('view')
        ? (string) $comment->get('field_eth_message')->value : '',
      '#files' => $comment->get('field_eth_files')->access('view')
        ? $comment->get('field_eth_files')->view(['type' => 'file_default', 'label' => 'hidden']) : [],
    ];
    if ($with_task) {
      $task = event_task_hub_task_for_field($comment->getFieldName());
      $node = $comment->getCommentedEntity();
      $item['#task_label'] = $task && $node ? $node->getFieldDefinition($task)->getLabel() : '';
      // Global history repeats the same comment as its task thread.
      $item['#anchor_prefix'] = 'history-';
    }
    foreach (['edit' => 'update', 'delete' => 'delete'] as $link => $operation) {
      if ($comment->access($operation)) {
        $item['#operations'][$link] = ['#type' => 'link', '#title' => $link === 'edit' ? t('Edit') : t('Delete'), '#url' => $comment->toUrl($link === 'edit' ? 'edit-form' : 'delete-form')];
      }
    }
    return $item;
  }

  private function history(NodeInterface $node): array {
    $tasks = [];
    $fields = [];
    foreach (array_keys(Setup::tasks()) as $task) {
      $field = Setup::discussionField($task);
      if ($node->hasField($task) && !$node->get($task)->isEmpty() && $node->get($task)->access('view')
          && $node->hasField($field) && (int) $node->get($field)->status !== 0 && $node->get($field)->access('view')) {
        $tasks[$task] = $node->getFieldDefinition($task)->getLabel();
        $fields[$task] = $field;
      }
    }
    $request = \Drupal::request()->query;
    $selected = $request->get('eth_task', '');
    $limit = (int) $request->get('eth_limit', 10);
    $limit = in_array($limit, [5, 10, 25, 50], TRUE) ? $limit : 10;
    if (is_string($selected) && isset($fields[$selected])) {
      $fields = [$selected => $fields[$selected]];
    }
    $build = [
      '#theme' => 'event_task_hub_history',
      '#filter' => \Drupal::formBuilder()->getForm(\Drupal\event_task_hub\Form\HistoryFilterForm::class, $node, $tasks),
      '#items' => [], '#pager' => [], '#start' => 0, '#end' => 0, '#total' => 0,
      '#cache' => ['max-age' => 0, 'contexts' => ['user', 'url.query_args']],
    ];
    if (!$fields) {
      return $build;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $query = $storage->getQuery()->accessCheck(FALSE)
      ->condition('comment_type', Setup::COMMENT_TYPE)->condition('entity_type', 'node')
      ->condition('entity_id', $node->id())->condition('field_name', array_values($fields), 'IN')
      ->condition('status', 1);
    $total = (int) (clone $query)->count()->execute();
    $pager = \Drupal::service('pager.manager')->createPager($total, $limit, 0);
    $offset = $pager->getCurrentPage() * $limit;
    $ids = $query->sort('created', 'DESC')->sort('cid', 'DESC')->range($offset, $limit)->execute();
    foreach ($storage->loadMultiple($ids) as $comment) {
      if ($comment->access('view')) {
        $build['#items'][] = $this->commentItem($comment, TRUE);
      }
    }
    $build['#total'] = $total;
    $build['#start'] = $total ? $offset + 1 : 0;
    $build['#end'] = min($offset + $limit, $total);
    $build['#pager'] = ['#type' => 'pager', '#element' => 0];
    return $build;
  }

  /** Builds escaped help text, linking only approved URI schemes. */
  private function help(array|string $lines): array {
    $build = [];
    foreach ((array) $lines as $line) {
      $paragraph = ['#type' => 'container', '#attributes' => ['class' => ['eth-help']]];
      $parts = preg_split('/(\[[^\]]+\]\([^)\s]+\))/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
      foreach ($parts as $part) {
        if (preg_match('/^\[([^\]]+)\]\((https?:\/\/[^\s]+|mailto:[^\s]+|\/(?!\/)[^\s]+)\)$/', $part, $match)) {
          $uri = str_starts_with($match[2], '/') ? 'internal:' . $match[2] : $match[2];
          $paragraph[] = ['#type' => 'link', '#title' => $match[1], '#url' => Url::fromUri($uri)];
        }
        else {
          $paragraph[] = ['#plain_text' => $part];
        }
      }
      $build[] = $paragraph;
    }
    return $build;
  }

  private function dateLabel(string $value): string {
    $zone = \Drupal::config('system.date')->get('timezone.default') ?: 'UTC';
    $date = new \DateTimeImmutable($value, new \DateTimeZone($zone));
    return \Drupal::service('date.formatter')->format($date->getTimestamp(), 'custom', 'F j, Y', $zone);
  }

  private function launch(NodeInterface $node): array {
    $values = [];
    foreach (['target' => 'field_target_launch_date', 'actual' => 'field_actual_launch_date'] as $key => $name) {
      if ($node->hasField($name) && !$node->get($name)->isEmpty() && $node->get($name)->access('view')) {
        $values[$key] = (string) $node->get($name)->value;
      }
    }
    if (isset($values['actual'])) {
      return ['label' => t('Launched'), 'date' => $values['actual'], 'date_label' => $this->dateLabel($values['actual']), 'state' => 'launched'];
    }
    if (isset($values['target'])) {
      $zone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone.default') ?: 'UTC');
      $today = new \DateTimeImmutable('today', $zone);
      $date = new \DateTimeImmutable($values['target'], $zone);
      $days = (int) $today->diff($date)->format('%r%a');
      return [
        'label' => $days < 0 ? t('Past target date') : ($days <= 3 ? t('Launching soon') : t('On track')),
        'date' => $values['target'], 'date_label' => $this->dateLabel($values['target']), 'state' => $days < 0 ? 'overdue' : ($days <= 3 ? 'soon' : 'on-track'),
      ];
    }
    return ['label' => t('Target launch date not set'), 'date' => '', 'state' => 'not-set'];
  }

}
