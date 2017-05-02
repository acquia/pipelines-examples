<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\ScheduledUpdateAccessControlHandler.
 */

namespace Drupal\scheduled_updates;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\scheduled_updates\Entity\ScheduledUpdate;

/**
 * Access controller for the Scheduled update entity.
 *
 * @see \Drupal\scheduled_updates\Entity\ScheduledUpdate.
 */
class ScheduledUpdateAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var ScheduledUpdate $entity */

    if ($account->hasPermission('administer scheduled updates')) {
      return AccessResult::allowed();
    }

    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view scheduled update entities');
    }
    $type_id = $entity->bundle();
    if ($entity->getOwnerId() == $account->id()) {
      // If owner that needs either own or any permission, not both.
      return AccessResult::allowedIfHasPermissions(
        $account,
        [
          "$operation any $type_id scheduled updates",
          "$operation own $type_id scheduled updates",
        ],
        'OR'
      );
    }
    else {
      return AccessResult::allowedIfHasPermission($account, "$operation any $type_id scheduled updates");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      'administer scheduled updates',
      "create $entity_bundle scheduled updates",
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }


}
