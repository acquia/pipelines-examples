<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Entity\ScheduledUpdateType.
 */

namespace Drupal\scheduled_updates\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\scheduled_updates\ScheduledUpdateTypeInterface;

/**
 * Defines the Scheduled update type entity.
 *
 * @ConfigEntityType(
 *   id = "scheduled_update_type",
 *   label = @Translation("Scheduled update type"),
 *   handlers = {
 *     "list_builder" = "Drupal\scheduled_updates\ScheduledUpdateTypeListBuilder",
 *     "access" = "Drupal\scheduled_updates\ScheduledUpdateTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\scheduled_updates\Form\ScheduledUpdateTypeForm",
 *       "edit" = "Drupal\scheduled_updates\Form\ScheduledUpdateTypeForm",
 *       "delete" = "Drupal\scheduled_updates\Form\ScheduledUpdateTypeDeleteForm",
 *       "add-as-field" = "Drupal\scheduled_updates\Form\ScheduledUpdateTypeAddAsFieldForm"
 *     }
 *   },
 *   config_prefix = "scheduled_update_type",
 *   admin_permission = "administer scheduled update types",
 *   bundle_of = "scheduled_update",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/workflow/scheduled-update-type/{scheduled_update_type}/edit",
 *     "delete-form" = "/admin/config/workflow/scheduled-update-type/{scheduled_update_type}/delete",
 *     "collection" = "/admin/config/workflow/scheduled-update-type/list",
 *     "clone-fields" = "/admin/config/workflow/scheduled-update-type/{scheduled_update_type}/clone-fields"
 *   }
 * )
 */
class ScheduledUpdateType extends ConfigEntityBundleBase implements ScheduledUpdateTypeInterface {
  /**
   * The Scheduled update type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Scheduled update type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The updates types this Scheduled Update types supports.
   *
   * Know update types are embedded and indepedent.
   * @var array
   */
  protected $update_types_supported = [];

  /**
   * @return array
   */
  public function getUpdateTypesSupported() {
    return $this->update_types_supported;
  }

  /**
   * @param array $update_types_supported
   */
  public function setUpdateTypesSupported($update_types_supported) {
    $this->update_types_supported = $update_types_supported;
  }

  /**
   * @param array $field_map
   */
  public function setFieldMap($field_map) {
    $this->field_map = $field_map;
  }

  /**
   * The entity type id of the entities to be updated.
   *
   * @var string
   */
  protected $update_entity_type;

  /**
   * The map of the source and destination fields
   *
   * The keys of the array are the source fields and the values are the destination fields.
   * @var array
   */
  protected $field_map = [];

  /**
   * @var array
   */
  protected $update_runner = [
    'id' => 'default_embedded'
  ];

  /**
   * @return array
   */
  public function getUpdateRunnerSettings() {
    return $this->update_runner;
  }

  /**
   * @return array
   */
  public function getFieldMap() {
    if (!$this->field_map) {
      $this->field_map = [];
    }
    return $this->field_map;
  }

  /**
   * @return string
   */
  public function getUpdateEntityType() {
    return $this->update_entity_type;
  }

  /**
   * @param string $update_entity_type
   */
  public function setUpdateEntityType($update_entity_type) {
    $this->update_entity_type = $update_entity_type;
  }

  public function isEmbeddedType() {
    return in_array('embedded', $this->update_types_supported);
  }

  public function isIndependentType() {
    return in_array('independent', $this->update_types_supported);
  }

  public function cloneField($new_field) {

  }

  /**
   * {@inheritdoc}
   */
  public function addNewFieldMappings($new_map) {
    $this->setFieldMap($new_map + $this->field_map);
  }


}
