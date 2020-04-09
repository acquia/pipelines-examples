<?php

namespace Drupal\workbench_moderation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\workbench_moderation\ModerationStateInterface;

/**
 * Defines the Moderation state entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_state",
 *   label = @Translation("Moderation state"),
 *   handlers = {
 *     "list_builder" = "Drupal\workbench_moderation\ModerationStateListBuilder",
 *     "access" = "Drupal\workbench_moderation\ModerationStateAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\workbench_moderation\Form\ModerationStateForm",
 *       "edit" = "Drupal\workbench_moderation\Form\ModerationStateForm",
 *       "delete" = "Drupal\workbench_moderation\Form\ModerationStateDeleteForm"
 *     },
 *   },
 *   config_prefix = "moderation_state",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/moderation-state/states/{moderation_state}/edit",
 *     "delete-form" = "/admin/structure/moderation-state/states/{moderation_state}/delete",
 *     "collection" = "/admin/structure/moderation-state/states"
 *   }
 * )
 */
class ModerationState extends ConfigEntityBase implements ModerationStateInterface {
  /**
   * The Moderation state ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether this state represents a published node.
   *
   * @var bool
   */
  protected $published;

  /**
   * Whether this state represents a default revision of the node.
   *
   * If this is a published state, then this property is ignored.
   *
   * @var bool
   */
  protected $default_revision;

  /**
   * {@inheritdoc}
   */
  public function isPublishedState() {
    return $this->published;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevisionState() {
    return $this->published || $this->default_revision;
  }

}
