<?php
/**
 * Author: Ted Bowman
 * Date: 1/15/16
 * Time: 10:28 AM
 */
namespace Drupal\scheduled_updates;
use Drupal\Core\Entity\ContentEntityInterface;


/**
 * Service to determine information about Scheduled Update Types
 */
interface UpdateUtilsInterface {
  /**
   * Determine a scheduled update type supports creating new revisions on
   * update.
   *
   * This is determined by the entity type it updates.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @return bool
   */
  public function supportsRevisionUpdates(ScheduledUpdateTypeInterface $scheduledUpdateType);

  /**
   * Determine if the entity type being update support default revision
   * setting.
   *
   * For now only nodes are supported.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function supportsRevisionBundleDefault(ScheduledUpdateTypeInterface $scheduledUpdateType);

  /**
   * Determines if an update supports revisions
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   *
   * @return bool
   */
  public function isRevisionableUpdate(ScheduledUpdateInterface $update);

  /**
   * Get the update type for an update.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   *
   * @return \Drupal\scheduled_updates\Entity\ScheduledUpdateType ;
   */
  public function getUpdateType(ScheduledUpdateInterface $update);

  public function getUpdateTypeLabel(ScheduledUpdateInterface $update);

  /**
   * Get whether a new revision should be created by default for this entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_to_update
   *
   * @return bool
   */
  public function getRevisionDefault(ContentEntityInterface $entity_to_update);

  /**
   * Set revision creation time for entities that support it.
   *
   * Currently only nodes and entities that use Entity API are supported.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function setRevisionCreationTime(ContentEntityInterface $entity);

  /**
   * Determines if entity type being updated supports Revision Ownership.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @return bool
   */
  public function supportsRevisionOwner(ScheduledUpdateTypeInterface $scheduledUpdateType);

  /**
   * Determines if the entity type being updated supports ownership.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @return bool
   */
  public function supportsOwner(ScheduledUpdateTypeInterface $scheduledUpdateType);

  /**
   * Get the directly previous revision.
   *
   * $entity->original will not ALWAYS be the previous revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function getPreviousRevision(ContentEntityInterface $entity);

  /**
   * Returns the revision ID of the latest revision of the given entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return int
   *   The revision ID of the latest revision for the specified entity, or
   *   NULL if there is no such entity.
   */
  public function getLatestRevisionId($entity_type_id, $entity_id);

  /**
   * Loads the latest revision of a specific entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The latest entity revision or NULL, if the entity type / entity doesn't
   *   exist.
   */
  public function getLatestRevision($entity_type_id, $entity_id);

  /**
   * Create select element bundle options for entity type.
   * @param $entity_type
   *
   * @return array
   */
  public function bundleOptions($entity_type);
}
