<?php
/**
 * @file Contains Drupal\scheduled_updates\Plugin\UpdateRunnerInterface.
 */

namespace Drupal\scheduled_updates\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for Update Runner Plugins.
 *
 * Update Runners control on updates are run and how they are connected to entities.
 */
interface UpdateRunnerInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface, PluginFormInterface {

  // Constants for Invalid Updates
  const INVALID_DELETE = 'DELETE';

  const INVALID_REQUEUE = 'REQUEUE';

  const INVALID_ARCHIVE = 'ARCHIVE';

  // Constants for behavior after updates are run.
  const AFTER_DELETE = 'DELETE';
  const AFTER_ARCHIVE = 'ARCHIVE';

  // Constants for revisions.
  const REVISIONS_BUNDLE_DEFAULT = 'BUNDLE_DEFAULT';
  const REVISIONS_YES = 'YES';
  const REVISIONS_NO = 'NO';

  // Constants for update user
  const USER_UPDATE_RUNNER = 'USER_UPDATE_RUNNER';
  const USER_OWNER = 'USER_OWNER';
  const USER_REVISION_OWNER = 'USER_REVISION_OWNER';
  const USER_UPDATE_OWNER = 'USER_UPDATE_OWNER';

  /**
   * Add all updates to queue.
   */
  public function addUpdatesToQueue();

  /**
   * Get the Queue for this Update Runner.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue object.
   */
  public function getQueue();

  /**
   * Run all updates that are in the queue.
   *
   * @param $time_end
   *  The time in seconds when no more updates should be run.
   *
   * @return
   */
  public function runUpdatesInQueue($time_end);

  /**
   * Get how this runner should handle invalid entity updates.
   *
   * @return string
   */
  public function getInvalidUpdateBehavior();

  /**
   * Get all field ids that are attached to the entity type to be updated and
   * target this update type.
   *
   * @return array
   */
  public function getReferencingFieldIds();

  /**
   * Return the entity id of the entity type being updated.
   *
   * @return string
   */
  public function updateEntityType();

  /**
   * Get target entity ids for an entity reference field on a entity.
   *
   * @todo Is there a way to do this with core Field API?
   *
   * @todo move this to a Utils trait or class.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $field_name
   *
   * @param bool $sort
   *
   * @return array Entity Ids for field values.
   * Entity Ids for field values.
   */
  public function getEntityReferenceTargetIds(ContentEntityInterface $entity, $field_name, $sort = FALSE);

  /**
   * Get the description of the Runner Plugin.
   *
   * Usually this will return description from the plugin itself but some runners
   * may need a dynamic description or a long description.
   *
   * @return string
   */
  public function getDescription();

}
