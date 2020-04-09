<?php

namespace Drupal\workbench_moderation;


use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ModerationStateTransitionStorage extends ConfigEntityStorage implements EntityHandlerInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    /* @var \Drupal\workbench_moderation\ModerationStateTransitionInterface $entity */
    $entity = parent::doCreate($values);
    return $entity->setModerationStateConfigPrefix($this->entityTypeManager->getDefinition('moderation_state')->getConfigPrefix());
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records) {
    $entities = parent::mapFromStorageRecords($records);
    $prefix = $this->entityTypeManager->getDefinition('moderation_state')->getConfigPrefix();
    /* @var \Drupal\workbench_moderation\ModerationStateTransitionInterface $entity */
    foreach ($entities as &$entity) {
      $entity->setModerationStateConfigPrefix($prefix);
    }
    reset($entities);
    return $entities;
  }

}
