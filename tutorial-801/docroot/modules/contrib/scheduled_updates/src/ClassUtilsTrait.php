<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\ClassUtilsTrait.
 */


namespace Drupal\scheduled_updates;


use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Trait for Class related functions.
 *
 * Helper function where 2 interfaces support the same functionality
 * or when an entity may or may not implement an interface.
 *
 * Some these might be necessary in Drupal 8.1 if Entity API modules changes get into core.
 */
trait ClassUtilsTrait {

  /**
   * Determines if an object or class name implements any interfaces in a list.
   *
   * Convenience function around class_implements.
   *
   * @param string|object $toCheck
   * @param array $interfaces
   *
   * @return boolean
   */
  protected function implementsInterface($toCheck, array $interfaces) {
    if (empty($toCheck)) {
      return FALSE;
    }
    if (array_intersect($interfaces, class_implements($toCheck))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get class names of interfaces that support revision ownership.
   *
   * Core and Entity API interfaces.
   *
   * @return array
   */
  protected function revisionOwnerInterfaces() {
    return [
      'Drupal\entity\Revision\EntityRevisionLogInterface',
      'Drupal\node\NodeInterface'
    ];
  }

  /**
   * Get the revision owner for an ContentEntity.
   *
   * Need because 2 possible interfaces support this.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\user\UserInterface|NULL
   */
  protected function getRevisionOwner(ContentEntityInterface $entity) {
    if ($entity instanceof NodeInterface) {
      return $entity->getRevisionAuthor();
    }
    elseif ($this->implementsInterface($entity, ['Drupal\entity\Revision\EntityRevisionLogInterface'])){
      /** @var \Drupal\entity\Revision\EntityRevisionLogInterface $entity */
      return $entity->getRevisionUser();
    }
    return NULL;
  }

  /**
   * Get the entity owner if applicable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\user\UserInterface|null
   */
  protected function getEntityOwner(ContentEntityInterface $entity) {
    if ($entity instanceof EntityOwnerInterface) {
      return $entity->getOwner();
    }
    return NULL;
  }

  /**
   * @return EntityTypeManagerInterface
   */
  protected function entityTypeManager() {
    if (isset($this->entityTypeManager)) {
      return $this->entityTypeManager;
    }
    return NULL;
  }

  protected function entityLabel($type_id) {
    return $this->entityTypeManager()->getDefinition($type_id)->getLabel();
  }

  protected function bundleLabel($type_id) {
    return $this->entityTypeManager()->getDefinition($type_id)->getBundleLabel();
  }

  protected function typeSupportsBundles($type_id) {
    $bundle_type = $this->entityTypeManager()->getDefinition($type_id)->getBundleEntityType();
    if (empty($bundle_type)) {
      return FALSE;
    }
    return TRUE;
  }

  public function targetTypeLabel(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    return $this->entityLabel($scheduledUpdateType->getUpdateEntityType());
  }

  protected function targetTypeBundleLabel(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    return $this->bundleLabel($scheduledUpdateType->getUpdateEntityType());
  }

  protected function targetSupportBundles(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    return $this->typeSupportsBundles($scheduledUpdateType->getUpdateEntityType());
  }

  /**
   * Determines if the class for an entity type definition implements and interface.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $type
   * @param array $interfaces
   *
   * @return bool
   */
  protected function definitionClassImplementsInterface(EntityTypeInterface $type, array $interfaces) {
    return $this->implementsInterface($type->getClass(), $interfaces);
  }


}
