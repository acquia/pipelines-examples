<?php

namespace Drupal\scheduled_updates;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Service to determine information about Scheduled Update Types.
 */
class UpdateUtils implements UpdateUtilsInterface {
  use ClassUtilsTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * UpdateUtils constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $bundleInfo) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRevisionUpdates(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    $type_definition = $this->getUpdateTypeDefinition($scheduledUpdateType);
    if ( $type_definition
      && $type_definition->isRevisionable()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRevisionBundleDefault(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    if ($type_definition = $this->getUpdateTypeDefinition($scheduledUpdateType)) {
      $bundle_type_id = $type_definition->getBundleEntityType();
      $bundle_class = $this->entityTypeManager->getDefinition($bundle_type_id)->getClass();
      // Core doesn't have a standard method for determining if entities of bundle should have new revisions.
      // This should be updated if an interface is create for this.
      // @todo Check Entity API has a solution for this and also check it's interface.
      if ($this->implementsInterface($bundle_class,['Drupal\node\NodeTypeInterface'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionableUpdate(ScheduledUpdateInterface $update) {
    return $this->supportsRevisionUpdates($this->getUpdateType($update));
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateType(ScheduledUpdateInterface $update) {
    return $this->entityTypeManager->getStorage('scheduled_update_type')->load($update->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateTypeLabel(ScheduledUpdateInterface $update) {
    return $this->getUpdateType($update)->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDefault(ContentEntityInterface $entity_to_update) {
    $bundle_type_id = $entity_to_update->getEntityType()->getBundleEntityType();
    $bundle = $this->entityTypeManager->getStorage($bundle_type_id)->load($entity_to_update->bundle());

    // This should exist because of previous check in
    // supportsRevisionBundleDefault but just in case.
    if ($bundle instanceof NodeTypeInterface) {
      return $bundle->isNewRevision();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision($entity_type_id, $entity_id) {
    if ($latest_revision_id = $this->getLatestRevisionId($entity_type_id, $entity_id)) {
      return $this->entityTypeManager->getStorage($entity_type_id)->loadRevision($latest_revision_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->pager(1)
        ->execute();
      if ($revision_ids) {
        $revision_id = array_keys($revision_ids)[0];
        return $revision_id;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime(ContentEntityInterface $entity) {
    $revision_timestamp_interfaces = [
      'Drupal\entity\Revision\EntityRevisionLogInterface',
      'Drupal\node\NodeInterface',
    ];
    if ($this->implementsInterface($entity, $revision_timestamp_interfaces)) {
      /** @var \Drupal\entity\Revision\EntityRevisionLogInterface|\Drupal\node\NodeInterface $entity */
      $entity->setRevisionCreationTime(REQUEST_TIME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRevisionOwner(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    if ($definition = $this->getUpdateTypeDefinition($scheduledUpdateType)) {
      return $this->definitionClassImplementsInterface(
        $definition,
        $this->revisionOwnerInterfaces()
      );
    }
    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function supportsOwner(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    if ($type = $this->getUpdateTypeDefinition($scheduledUpdateType)) {
      return $this->definitionClassImplementsInterface($type, ['Drupal\user\EntityOwnerInterface']);
    }
    return FALSE;
  }

  /**
   * Get the entity definition for the entity to be updated.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @return array|\Drupal\Core\Entity\EntityTypeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUpdateTypeDefinition(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    if ($update_entity_type = $scheduledUpdateType->getUpdateEntityType()) {
      return $this->entityTypeManager->getDefinition($update_entity_type);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousRevision(ContentEntityInterface $entity) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $query = $storage->getQuery();
    $type = $entity->getEntityType();
    $query->allRevisions()
      ->condition($type->getKey('id'), $entity->id())
      ->condition($type->getKey('revision'), $entity->getRevisionId(), '<')
      ->sort($type->getKey('revision'), 'DESC')
      ->pager(1);
    $revision_ids = $query->execute();
    if ($revision_ids) {
      $revision_id = array_keys($revision_ids)[0];
      return $storage->loadRevision($revision_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleOptions($entity_type) {
    $info = $this->bundleInfo->getBundleInfo($entity_type);
    $options = [];
    foreach ($info as $bundle => $bundle_info) {
      $options[$bundle] = $bundle_info['label'];
    }
    return $options;
  }

}
