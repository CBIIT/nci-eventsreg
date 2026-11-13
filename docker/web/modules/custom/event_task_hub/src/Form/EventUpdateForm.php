<?php

namespace Drupal\event_task_hub\Form;

use Drupal\comment\CommentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\event_task_hub\Controller\DiscussionController;
use Drupal\event_task_hub\Setup;

/** Native comment form with an accessible task selector for the event page. */
final class EventUpdateForm extends CommentForm {

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $node = $this->entity->getCommentedEntity();
    $options = (new DiscussionController())->selectableTasks($node, $this->currentUser);
    $form['related_to_task'] = [
      '#type' => 'select', '#title' => $this->t('Related to Task:'),
      '#options' => $options, '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE, '#weight' => -50,
      // Do not silently pick the first task on the global update form.
      '#default_value' => $form_state->getValue('related_to_task', ''),
    ];
    $form['#attached']['library'][] = 'event_task_hub/tasks';
    $form['#action'] = Url::fromRoute('event_task_hub.update', ['node' => $node->id()])->toString();
    if (isset($form['field_eth_message']['widget'][0]['value'])) {
      $form['field_eth_message']['widget'][0]['value']['#title'] = $this->t('Comments');
    }
    return $form;
  }

  public function buildEntity(array $form, FormStateInterface $form_state) {
    $comment = parent::buildEntity($form, $form_state);
    $task = $form_state->getValue('related_to_task');
    $node = $comment->getCommentedEntity();
    $options = (new DiscussionController())->selectableTasks($node, $this->currentUser);
    if (is_string($task) && isset($options[$task])) {
      $comment->set('field_name', Setup::discussionField($task));
    }
    return $comment;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);
    $node = $this->entity->getCommentedEntity();
    $options = (new DiscussionController())->selectableTasks($node, $this->currentUser);
    $task = $form_state->getValue('related_to_task');
    if (!is_string($task) || !isset($options[$task])) {
      $form_state->setErrorByName('related_to_task', $this->t('Choose an available task.'));
    }
    return $entity;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Submit');
    return $actions;
  }

}
