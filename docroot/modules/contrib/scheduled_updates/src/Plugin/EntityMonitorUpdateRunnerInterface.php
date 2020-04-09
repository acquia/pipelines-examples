<?php
/**
 * Author: Ted Bowman
 * Date: 1/13/16
 * Time: 3:25 PM
 */

namespace Drupal\scheduled_updates\Plugin;


use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface Update runners that need to monitor entities.
 *
 */
interface EntityMonitorUpdateRunnerInterface extends UpdateRunnerInterface {

  /**
   * Fires when entity of type to be updated is changed.
   *
   * This function is fired every time and entity of the type that the Scheduled Update Type is up
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return mixed
   */
  public function onEntityUpdate(ContentEntityInterface $entity);

  // @todo do is onEntityDelete necessary?
}
