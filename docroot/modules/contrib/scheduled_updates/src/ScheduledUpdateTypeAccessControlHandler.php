<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\ScheduledUpdateTypeAccessControlHandler.
 */

namespace Drupal\scheduled_updates;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the node type entity type.
 *
 * @see \Drupal\scheduled_updates\entity\ScheduledUpdateType
 */
class ScheduledUpdateTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view scheduled update entities');
        break;

      case 'delete':
        return parent::checkAccess($entity, $operation, $account)->cacheUntilEntityChanges($entity);
        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
        break;
    }
  }

}
