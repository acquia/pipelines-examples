<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Permissions.
 */

namespace Drupal\scheduled_updates;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\scheduled_updates\Entity\ScheduledUpdateType;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class Permissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function scheduledUpdateTypesPermissions() {
    $perms = array();
    // Generate scheduled_update permissions for all scheduled updates types.
    foreach (ScheduledUpdateType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of scheduled updates permissions for a given scheduled udpate type.
   *
   * @param \Drupal\scheduled_updates\entity\ScheduledUpdateType|\Drupal\scheduled_updates\ScheduledUpdateTypeInterface $type
   *   The node type.
   *
   * @return array An associative array of permission names and descriptions.
   * An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ScheduledUpdateTypeInterface $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "create $type_id scheduled updates" => array(
        'title' => $this->t('%type_name: Create new scheduled updates', $type_params),
      ),
      "edit own $type_id scheduled updates" => array(
        'title' => $this->t('%type_name: Edit own scheduled updates', $type_params),
      ),
      "edit any $type_id scheduled updates" => array(
        'title' => $this->t('%type_name: Edit any scheduled updates', $type_params),
      ),
      "delete own $type_id scheduled updates" => array(
        'title' => $this->t('%type_name: Delete own scheduled updates', $type_params),
      ),
      "delete any $type_id scheduled updates" => array(
        'title' => $this->t('%type_name: Delete any scheduled updates', $type_params),
      ),
      /*
      "view $type_id revisions" => array(
        'title' => $this->t('%type_name: View revisions', $type_params),
      ),
      "revert $type_id revisions" => array(
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for nodes in question, or <em>administer nodes</em>.'),
      ),
      "delete $type_id revisions" => array(
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for nodes in question, or <em>administer nodes</em>.'),
      ),
      */
    );
  }

}
