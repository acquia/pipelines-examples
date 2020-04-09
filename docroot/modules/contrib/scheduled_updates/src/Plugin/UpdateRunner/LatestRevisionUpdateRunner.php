<?php
/**
 * @file
 * Contains
 * \Drupal\scheduled_updates\Plugin\UpdateRunner\LatestRevisionUpdateRunner
 */


namespace Drupal\scheduled_updates\Plugin\UpdateRunner;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\scheduled_updates\ScheduledUpdateTypeInterface;

/**
 * The Latest Revision Update Runner.
 *
 * @todo Is spliting the description onto multiple lines a good idea?
 *       It works but is ugly.
 *
 * @UpdateRunner(
 *   id = "latest_revision",
 *   label = @Translation("Latest Revision"),
 *   update_types = {"embedded"},
 *   description = @Translation("Runs updates always against the latest revision of revisionable entities content.")
 * )
 */
class LatestRevisionUpdateRunner extends EmbeddedUpdateRunner {
/*
 * Runs updates always against the latest revision of revisionable entities content.
 *
 */
  /**
   * {@inheritdoc}
   *
   * This method is overridden because the version in BaseUpdateRunner only needs
   * to get default revisions so does not call $query->allRevisions().
   *
   * $query->condition("$field_id.entity.update_timestamp", $all_ready_update_ids, 'IN');
   *
   *
   */
  protected function getEntityIdsReferencingReadyUpdates() {
    $entity_ids = [];
    if ($field_ids = $this->getReferencingFieldIds()) {
      $entity_storage = $this->entityTypeManager->getStorage($this->updateEntityType());
      $all_ready_update_ids = $this->getReadyUpdateIds();
      if ($all_ready_update_ids) {
        foreach ($field_ids as $field_id) {
          $query = $entity_storage->getQuery('AND');
          $query->condition("$field_id.target_id", $all_ready_update_ids, 'IN');

          $query->allRevisions();
          $entity_ids += $query->execute();
        }
      }
    }
    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadEntitiesToUpdate($entity_ids) {
    $revision_ids = array_keys($entity_ids);
    $entity_ids = array_unique($entity_ids);
    $revisions = [];
    foreach ($entity_ids as $entity_id) {
      /** @var ContentEntityInterface $latest_revision */
      $latest_revision = $this->updateUtils->getLatestRevision($this->updateEntityType(), $entity_id);
      // Check the latest revision was in the revisions sent to this function.
      if (in_array($latest_revision->getRevisionId(), $revision_ids)) {
        $revisions[$entity_id] = $latest_revision;
      }
    }
    return $revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var ScheduledUpdateTypeInterface $scheduled_update_type */
    $scheduled_update_type = $form_state->get('scheduled_update_type');
    // Check if entity type to be updated supports revisions.
    if (!$this->updateUtils->supportsRevisionUpdates($scheduled_update_type)) {
      // @todo Check if any bundles in update entity type is moderated
      $form_state->setError(
        $form['update_entity_type'],
        $this->t('The latest revision runner cannot be used with an entity type that does not support revisions.'
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Runs updates always against the latest revision of revisionable entities content.') .
      ' ' . t('This is useful for modules that allow forward revisioning such as Workbench Moderation.') .
      ' ' . t('This Update Runner can only be used with revisionable entity types.');
  }


}
