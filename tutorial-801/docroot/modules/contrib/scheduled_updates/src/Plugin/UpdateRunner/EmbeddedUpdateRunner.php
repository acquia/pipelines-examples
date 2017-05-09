<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Plugin\UpdateRunner\EmbeddedUpdateRunner.
 */


namespace Drupal\scheduled_updates\Plugin\UpdateRunner;


use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\scheduled_updates\Entity\ScheduledUpdate;
use Drupal\scheduled_updates\Plugin\BaseUpdateRunner;
use Drupal\scheduled_updates\Plugin\EntityMonitorUpdateRunnerInterface;
use Drupal\scheduled_updates\ScheduledUpdateInterface;

/**
 * The default Embedded Update Runner.
 *
 * @UpdateRunner(
 *   id = "default_embedded",
 *   label = @Translation("Embedded"),
 *   description = @Translation("Handles updates that are embedded on entities via entity reference fields."),
 *   update_types = {"embedded"}
 * )
 */
class EmbeddedUpdateRunner extends BaseUpdateRunner implements EntityMonitorUpdateRunnerInterface {
  /**
   * Return all schedule updates that are referenced via Entity Reference
   * fields.
   *
   * @return ScheduledUpdate[]
   */
  protected function getEmbeddedUpdates() {
    $updates = [];
    /** @var String[] $fields */
    if ($entity_ids = $this->getEntityIdsReferencingReadyUpdates()) {
      if ($entities = $this->loadEntitiesToUpdate($entity_ids)) {
        $field_ids = $this->getReferencingFieldIds();
        /** @var ContentEntityInterface $entity */
        foreach ($entities as $entity) {
          /** @var  $entity_update_ids - all update ids for this entity for our fields. */
          $entity_update_ids = [];
          /** @var  $field_update_ids - update ids keyed by field_id. */
          $field_update_ids = [];
          foreach ($field_ids as $field_id) {
            // Store with field id.
            $field_update_ids[$field_id] = $this->getEntityReferenceTargetIds($entity, $field_id);
            // Add to all for entity.
            $entity_update_ids += $field_update_ids[$field_id];
          }
          // For all entity updates return only those ready to run.
          $ready_update_ids = $this->getReadyUpdateIds($entity_update_ids);
          // Loop through updates attached to fields.
          foreach ($field_update_ids as $field_id => $update_ids) {
            // For updates attached to field get only those ready to run.
            $field_ready_update_ids = array_intersect($update_ids, $ready_update_ids);
            foreach ($field_ready_update_ids as $field_ready_update_id) {
              $updates[] = [
                'update_id' => $field_ready_update_id,
                // If this is revisionable entity use revision id as key for Runner Plugins that care about revisions.
                'entity_ids' => $entity->getRevisionId()? [$entity->getRevisionId() => $entity->id()]: [$entity->id()],
                'field_id' => $field_id,
                'entity_type' => $this->updateEntityType(),
              ];
            }
          }
        }
      }
    }
    return $updates;

  }

  /**
   * {@inheritdoc}
   */
  protected function getAllUpdates() {
    return $this->getEmbeddedUpdates();
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityUpdate(ContentEntityInterface $entity) {
    if ($this->updateUtils->supportsRevisionUpdates($this->scheduled_update_type)) {
      $this->deactivateUpdates($entity);
      $this->reactivateUpdates($entity);
    }

  }

  /**
   * Deactivate any Scheduled Updates that are previous revision but not on current.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  protected function deactivateUpdates(ContentEntityInterface $entity) {
    $current_update_ids = $this->getUpdateIdsOnEntity($entity);
    // Loop through all previous revisions and deactive updates not on current revision.
    $revisions = $this->getPreviousRevisionsWithUpdates($entity);
    if (empty($revisions)) {
      return;
    }
    $all_revisions_update_ids = [];
    foreach ($revisions as $revision) {
      // array_merge so so elements with same key are not replaced.
      $all_revisions_update_ids = array_merge($all_revisions_update_ids,$this->getUpdateIdsOnEntity($revision));
    }
    $all_revisions_update_ids = array_unique($all_revisions_update_ids);
    $updates_ids_not_on_current = array_diff($all_revisions_update_ids, $current_update_ids);
    if ($updates_ids_not_on_current) {
      $storage = $this->entityTypeManager->getStorage('scheduled_update');
      foreach ($updates_ids_not_on_current as $update_id) {
        /** @var ScheduledUpdateInterface $update */
        $update = $storage->load($update_id);
        $update->status = ScheduledUpdateInterface::STATUS_INACTIVE;
        $update->save();
      }
    }
  }

  /**
   * Reactive any updates that are on this entity that have been deactived previously.
   *
   * @see ::deactivateUpdates()
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  protected function reactivateUpdates(ContentEntityInterface $entity) {
    if ($update_ids = $this->getUpdateIdsOnEntity($entity)) {
      $storage = $this->entityTypeManager->getStorage('scheduled_update');
      $query = $storage->getQuery();
      $query->condition('status', [ScheduledUpdateInterface::STATUS_UNRUN, ScheduledUpdateInterface::STATUS_REQUEUED], 'NOT IN');
      $query->condition($this->entityTypeManager->getDefinition('scheduled_update')->getKey('id'), $update_ids, 'IN');
      $non_active_update_ids = $query->execute();
      $non_active_updates = $storage->loadMultiple($non_active_update_ids);
      foreach ($non_active_updates as $non_active_update) {
        $non_active_update->status = ScheduledUpdateInterface::STATUS_UNRUN;
      }
    }
  }

  /**
   * Get all update ids for this connected Update type.
   *
   * @todo Should results be cached per entity_id and revision_id to avoiding loading updates.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @param bool $include_inactive
   *
   * @return array
   */
  protected function getUpdateIdsOnEntity(ContentEntityInterface $entity, $include_inactive = FALSE) {
    $field_ids = $this->getReferencingFieldIds();
    $update_ids = [];
    foreach ($field_ids as $field_id) {
      $field_update_ids = $this->getEntityReferenceTargetIds($entity, $field_id);
      // This field could reference other update bundles
      // remove any that aren't of the attached scheduled update type.
      foreach ($field_update_ids as $field_update_id) {
        $update = $this->entityTypeManager->getStorage('scheduled_update')->load($field_update_id);
        if ($update && $update->bundle() == $this->scheduled_update_type->id()) {
          if (!$include_inactive) {
            if ($update->status->value == ScheduledUpdateInterface::STATUS_INACTIVE) {
              continue;
            }
          }
          $update_ids[$field_update_id] = $field_update_id;
        }
      }
    }
    return $update_ids;
  }

  /**
   * Get all previous revisions that have updates of the attached type.
   *
   * This function would be easier and more performant if this core issue with
   * Entity Query was fixed: https://www.drupal.org/node/2649268 Without this
   * fix can't filter query on type of update and whether they are active. So
   * therefore all previous revisions have to be loaded.
   *
   * @todo Help get that core issue fixed or rewrite this function query table
   *       fields directly.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected function getPreviousRevisionsWithUpdates(ContentEntityInterface $entity) {
    /** @var ContentEntityInterface[] $revisions */
    $revisions = [];
    $type = $entity->getEntityType();
    $query = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->getQuery();
    $query->allRevisions()
      ->condition($type->getKey('id'), $entity->id())
      ->condition($type->getKey('revision'), $entity->getRevisionId(), '<')
      ->sort($type->getKey('revision'), 'DESC');
    if ($revision_ids = $query->execute()) {
      $revision_ids = array_keys($revision_ids);
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      foreach ($revision_ids as $revision_id) {
        /** @var ContentEntityInterface $revision */
        $revision = $storage->loadRevision($revision_id);
        if ($update_ids = $this->getUpdateIdsOnEntity($revision)) {
          $revisions[$revision_id] = $revision;
        }
      }
    }
    return $revisions;
  }

}
