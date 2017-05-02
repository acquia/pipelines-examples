<?php


namespace Drupal\workbench_moderation;

use Drupal\Core\Database\Connection;

/**
 * Tracks metadata about revisions across entities.
 */
class RevisionTracker implements RevisionTrackerInterface {

  /**
   * Constructs a new RevisionTracker.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * RevisionTracker constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function setLatestRevision($entity_type, $entity_id, $langcode, $revision_id) {
    $this->recordLatestRevision($entity_type, $entity_id, $langcode, $revision_id);
    return $this;
  }

  /**
   * Records the latest revision of a given entity.
   *
   * @param $entity_type
   *   The machine name of the type of entity.
   * @param $entity_id
   *   The Entity ID in question.
   * @param $langcode
   *   The langcode of the revision we're saving. Each language has its own
   *   effective tree of entity revisions, so in different languages
   *   different revisions will be "latest".
   * @param $revision_id
   *   The revision ID that is now the latest revision.
   *
   * @return int
   *   One of the valid returns from a merge query's execute method.
   */
  protected function recordLatestRevision($entity_type, $entity_id, $langcode, $revision_id) {
    return $this->connection->merge('workbench_revision_tracker')
      ->keys([
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'langcode' => $langcode,
      ])
      ->fields([
        'revision_id' => $revision_id,
      ])
      ->execute();
  }

}
