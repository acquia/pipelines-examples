<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\FieldManagerInterface.
 */
namespace Drupal\scheduled_updates;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Interface for the field manager service.
 *
 * This service performs Scheduled Updates specific functions.
 */
interface FieldManagerInterface {
  /**
   * Clone field onto a Scheduled Update Type.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduled_update_type
   * @param $field_name
   *  Field name on the source entity type.
   *
   * @param null $field_config_id
   *  Id for configurable fields. Base fields will not have this.
   *
   * @return bool|\Drupal\field\Entity\FieldConfig
   */
  public function cloneField(ScheduledUpdateTypeInterface $scheduled_update_type, $field_name, $field_config_id = NULL, array $default_value = [], $hide = FALSE);

  /**
   * Checks if a field machine name is taken.
   *
   * Copied from \Drupal\field_ui\Form\FieldStorageAddForm::fieldNameExists
   *
   *
   *
   * @param $field_name
   * @param $entity_type_id
   *
   * @return bool Whether or not the field machine name is taken.
   * Whether or not the field machine name is taken.
   */
  public function fieldNameExists($field_name, $entity_type_id);

  /**
   * Get all the instances of field for an entity type.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   * @param string $entity_type_id
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getAllFieldConfigsForField(FieldStorageDefinitionInterface $definition, $entity_type_id);

  /**
   * Creates a new Entity Reference field that will reference the updates of this type.
   *
   * @param array $new_field_settings
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $update_type
   *
   * @return mixed
   */
  public function createNewReferenceField(array $new_field_settings, ScheduledUpdateTypeInterface $update_type);

  /**
   * Update existing Entity reference fields to have the bundle as a target.
   *
   * @param array $existing_field_settings
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $entity
   *
   * @return mixed
   */
  public function updateExistingReferenceFields(array $existing_field_settings, ScheduledUpdateTypeInterface $entity);
}
