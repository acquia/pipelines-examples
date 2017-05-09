<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\ScheduledUpdateTypeInterface.
 */

namespace Drupal\scheduled_updates;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Scheduled update type entities.
 */
interface ScheduledUpdateTypeInterface extends ConfigEntityInterface {
  // Add get/set methods for your configuration properties here.
  const REFERENCER = 1;

  const REFERENCED = 2;

  public function getUpdateEntityType();

  public function isEmbeddedType();

  public function isIndependentType();

  /**
   * @return array
   */
  public function getFieldMap();

  /**
   * Set field map.
   *
   * @param $field_map
   *  Keys are field names on Update Type
   *  Values are fields on destination entity type.
   */
  public function setFieldMap($field_map);

  public function cloneField($new_field);

  /**
   * Return update runner settings.
   *
   * @return array
   */
  public function getUpdateRunnerSettings();

  /**
   * Add new mappings to existing field map.
   *
   * New keys will replace on old keys.
   *
   * @param $new_map
   *  Keys are field names on Update Type
   *  Values are fields on destination entity type.
   */
  public function addNewFieldMappings($new_map);


}
