<?php

namespace Drupal\event_task_hub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/** Filters the native update history without a View. */
final class HistoryFilterForm extends FormBase {

  public function getFormId() {
    return 'event_task_hub_history_filter';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL, array $tasks = []) {
    $form_state->set('node_id', $node->id());
    $query = \Drupal::request()->query;
    $task = $query->get('eth_task', '');
    $limit = (int) $query->get('eth_limit', 10);
    $form['#attributes']['class'][] = 'eth-history-filter';
    $form['task'] = [
      '#type' => 'select', '#title' => $this->t('Related to Task:'),
      '#options' => ['' => $this->t('- Any -')] + $tasks,
      '#default_value' => is_string($task) && isset($tasks[$task]) ? $task : '',
    ];
    $form['limit'] = [
      '#type' => 'select', '#title' => $this->t('Items per page'),
      '#options' => [5 => 5, 10 => 10, 25 => 25, 50 => 50],
      '#default_value' => in_array($limit, [5, 10, 25, 50], TRUE) ? $limit : 10,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Apply')];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.node.canonical', ['node' => $form_state->get('node_id')], [
      'query' => ['eth_task' => $form_state->getValue('task'), 'eth_limit' => $form_state->getValue('limit')],
      'fragment' => 'eth-update-history',
    ]);
  }

}
