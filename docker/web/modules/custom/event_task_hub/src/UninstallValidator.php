<?php

namespace Drupal\event_task_hub;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;

/** Prevents accidental loss or exposure of stored event discussions. */
final class UninstallValidator implements ModuleUninstallValidatorInterface {

  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager) {}

  public function validate($module) {
    if ($module !== 'event_task_hub') {
      return [];
    }
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck(FALSE)
      ->condition('type', Setup::BUNDLE)->range(0, 1)->execute();
    $comments = $this->entityTypeManager->getStorage('comment')->getQuery()->accessCheck(FALSE)
      ->condition('comment_type', Setup::COMMENT_TYPE)->range(0, 1)->execute();
    return ($nodes || $comments)
      ? ['Event Task Hub still has event pages or task comments. Export/migrate the content before uninstalling. Updating the module does not require uninstalling it.'] : [];
  }

}
