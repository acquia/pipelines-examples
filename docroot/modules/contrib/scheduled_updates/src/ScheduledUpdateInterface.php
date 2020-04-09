<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\ScheduledUpdateInterface.
 */

namespace Drupal\scheduled_updates;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Scheduled update entities.
 *
 * @ingroup scheduled_updates
 */
interface ScheduledUpdateInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // @todo Add more status values: archived successfull, archived invalid
  const STATUS_UNRUN = 1;
  const STATUS_INQUEUE = 2;
  const STATUS_REQUEUED = 3;
  const STATUS_SUCCESSFUL = 4;
  const STATUS_UNSUCESSFUL = 5;
  const STATUS_INACTIVE = 6;

  /**
   * Set the entities to update for an update entity.
   *
   * This currently just sets the field value but does not save.
   *
   * @param array $update_entity_ids
   *
   */
  public function setUpdateEntityIds(array $update_entity_ids);
}
