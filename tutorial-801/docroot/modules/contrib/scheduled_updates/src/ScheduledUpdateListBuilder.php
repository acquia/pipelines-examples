<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\ScheduledUpdateListBuilder.
 *
 * @todo Replace with default View.
 */

namespace Drupal\scheduled_updates;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Scheduled update entities.
 *
 * @ingroup scheduled_updates
 */
class ScheduledUpdateListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  use ClassUtilsTrait;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface  */
  protected $entityTypeManager;

  /**
   * @var \Drupal\scheduled_updates\UpdateUtils
   */
  protected $updateUtils;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('scheduled_updates.update_utils')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\scheduled_updates\UpdateUtils $updateUtils
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UpdateUtils $updateUtils) {
    parent::__construct($entity_type, $storage);
    $this->updateUtils = $updateUtils;
  }
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Update Time');
    $header['type'] = $this->t('Update Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\scheduled_updates\Entity\ScheduledUpdate */
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.scheduled_update.edit_form', array(
          'scheduled_update' => $entity->id(),
        )
      )
    );

    $row['type'] = $this->updateUtils->getUpdateTypeLabel($entity);
    return $row + parent::buildRow($entity);
  }

}
