<?php

namespace Drupal\workbench_moderation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\workbench_moderation\ModerationStateTransitionInterface;

/**
 * Defines the Moderation state transition entity.
 *
 * @ConfigEntityType(
 *   id = "moderation_state_transition",
 *   label = @Translation("Moderation state transition"),
 *   handlers = {
 *     "list_builder" = "Drupal\workbench_moderation\ModerationStateTransitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\workbench_moderation\Form\ModerationStateTransitionForm",
 *       "edit" = "Drupal\workbench_moderation\Form\ModerationStateTransitionForm",
 *       "delete" = "Drupal\workbench_moderation\Form\ModerationStateTransitionDeleteForm"
 *     },
 *     "storage" = "Drupal\workbench_moderation\ModerationStateTransitionStorage"
 *   },
 *   config_prefix = "moderation_state_transition",
 *   admin_permission = "administer moderation state transitions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/moderation-state/transitions/{moderation_state_transition}/edit",
 *     "delete-form" = "/admin/structure/moderation-state/transitions/{moderation_state_transition}/delete",
 *     "collection" = "/admin/structure/moderation-state/transitions"
 *   }
 * )
 */
class ModerationStateTransition extends ConfigEntityBase implements ModerationStateTransitionInterface {
  /**
   * The Moderation state transition ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Moderation state transition label.
   *
   * @var string
   */
  protected $label;

  /**
   * ID of from state.
   *
   * @var string
   */
  protected $stateFrom;

  /**
   * ID of to state.
   *
   * @var string
   */
  protected $stateTo;

  /**
   * Relative weight of this transition.
   *
   * @var int
   */
  protected $weight;

  /**
   * Moderation state config prefix
   *
   * @var string.
   */
  protected $moderationStateConfigPrefix;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $prefix = $this->getModerationStateConfigPrefix() . '.';
    if ($this->stateFrom) {
      $this->addDependency('config', $prefix . $this->stateFrom);
    }
    if ($this->stateTo) {
      $this->addDependency('config', $prefix . $this->stateTo);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromState() {
    return $this->stateFrom;
  }

  /**
   * {@inheritdoc}
   */
  public function getToState() {
    return $this->stateTo;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Gets the moderation state config prefix.
   *
   * @return string
   *   Moderation state config prefix.
   */
  protected function getModerationStateConfigPrefix() {
    if (!isset($this->moderationStateConfigPrefix)) {
      $this->moderationStateConfigPrefix = \Drupal::service('entity_type.manager')->getDefinition('moderation_state')->getConfigPrefix();
    }
    return $this->moderationStateConfigPrefix;
  }

  /**
   * Sets the moderation state config prefix.
   *
   * @param string $moderation_state_config_prefix
   *   Moderation state config prefix.
   *
   * @return self
   *   Called instance.
   */
  public function setModerationStateConfigPrefix($moderation_state_config_prefix) {
    $this->moderationStateConfigPrefix = $moderation_state_config_prefix;
    return $this;
  }

}
