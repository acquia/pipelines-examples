<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\FieldUtilsTrait.
 */


namespace Drupal\scheduled_updates;


use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;

trait FieldUtilsTrait {


  protected function getDestinationFieldsOptions($entity_type_id, $source_field) {
    $destination_fields = $this->getDestinationFields($entity_type_id, $source_field);
    $options = [];
    foreach ($destination_fields as $field_id => $destination_field) {
      $options[$field_id] = $destination_field->getName();
    }
    return $options;
  }

  /**
   * Return all fields that can be used as destinations fields.
   *
   * @param $entity_type_id
   * @param \Drupal\field\Entity\FieldConfig $source_field
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected function getDestinationFields($entity_type_id, FieldConfig $source_field = NULL) {
    $destination_fields = [];

    $fields = $this->FieldManager()->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_id => $field) {
      if ($this->isDestinationFieldCompatible($field, $source_field)) {
        $destination_fields[$field_id] = $field;
      }
    }
    return $destination_fields;
  }

  protected function getBundleDestinationOptions($entity_type_id, $bundle) {
    $destination_fields = [];

    $fields = $this->FieldManager()->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field_id => $field) {
      if ($this->isDestinationFieldCompatible($field->getFieldStorageDefinition())) {
        $destination_fields[$field_id] = $field->getLabel();
      }
    }
    return $destination_fields;
  }

  protected function getFieldLabel($entity_type_id, $bundle, $field_name) {
    $fields = $this->FieldManager()->getFieldDefinitions($entity_type_id, $bundle);
    if (isset($fields[$field_name])) {
      return $fields[$field_name]->getLabel();
    }
    return '';
  }

  protected function getEntityDestinationOptions($entity_type_id) {
    $definitions = $this->getDestinationFields($entity_type_id);
    $options = [];
    foreach ($definitions as $definition) {
      $options[$definition->getName()] = $definition->getLabel();
    }
    return $options;
  }
  /**
   * Get Fields that can used as a destination field for this type.
   *
   * @param string $type
   *
   * @return array
   */
  protected function getMatchingFieldTypes($type) {
    // @todo which types can be interchanged
    return [$type];
  }

  /**
   * Check if a field on the entity type to update is a possible destination field.
   *
   * @todo Should this be on our FieldManager service?
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *  Field definition on entity type to update to check.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $source_field
   *  Source field to check compatibility against. If none then check generally.
   *
   * @return bool
   */
  protected function isDestinationFieldCompatible(FieldStorageDefinitionInterface $definition, FieldDefinitionInterface $source_field = NULL) {
    // @todo Create field definition wrapper class to treat FieldDefinitionInterface and FieldStorageDefinitionInterface the same.
    if ($definition instanceof BaseFieldDefinition && $definition->isReadOnly()) {
      return FALSE;
    }
    // Don't allow updates on updates!
    if ($definition->getType() == 'entity_reference') {
      if ($definition->getSetting('target_type') == 'scheduled_update') {
        return FALSE;
      }
    }

    if ($source_field) {
      $matching_types = $this->getMatchingFieldTypes($source_field->getType());
      if (!in_array($definition->getType(), $matching_types)) {
        return FALSE;
      }
      // Check cardinality
      $destination_cardinality = $definition->getCardinality();
      $source_cardinality = $source_field->getFieldStorageDefinition()->getCardinality();
      // $destination_cardinality is unlimited. It doesn't matter what source is.
      if ($destination_cardinality != -1) {
        if ($source_cardinality == -1) {
          return FALSE;
        }
        if ($source_cardinality > $destination_cardinality) {
          return FALSE;
        }
      }


      switch($definition->getType()) {
        case 'entity_reference':
          // Entity reference field must match entity target types.
          if ($definition->getSetting('target_type') != $source_field->getSetting('target_type')) {
            return FALSE;
          }
          // @todo Check bundles
          break;
        // @todo Other type specific conditions?
      }

    }
    return TRUE;
  }

  /**
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $updateType
   *
   * @return FieldConfig[] array
   */
  protected function getSourceFields(ScheduledUpdateTypeInterface $updateType) {
    $source_fields = [];
    $fields = $this->FieldManager()->getFieldDefinitions('scheduled_update', $updateType->id());
    foreach ($fields as $field_id => $field) {
      if (! $field instanceof BaseFieldDefinition) {
        $source_fields[$field_id] = $field;
      }
    }
    return $source_fields;
  }

  /**
   * Utility Function to load a single field definition.
   *
   * @param $entity_type_id
   * @param $field_name
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface|null
   */
  protected function getFieldStorageDefinition($entity_type_id, $field_name) {
    $definitions = $this->FieldManager()->getFieldStorageDefinitions($entity_type_id);
    if (!isset($definitions[$field_name])) {
      return NULL;
    }
    return $definitions[$field_name];
  }

  /**
   * Get field definition on bundle.
   *
   * @param $entity_type_id
   * @param $bundle
   * @param $field_name
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   */
  public function getFieldDefinition($entity_type_id, $bundle, $field_name) {
    $bundle_definitions = $this->FieldManager()->getFieldDefinitions($entity_type_id, $bundle);
    if (isset($bundle_definitions[$field_name])) {
      return $bundle_definitions[$field_name];
    }
    return NULL;
  }

  /**
   * @return EntityFieldManagerInterface;
   */
  abstract function FieldManager();

}
