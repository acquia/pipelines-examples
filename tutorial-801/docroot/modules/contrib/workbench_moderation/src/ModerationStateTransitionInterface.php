<?php

namespace Drupal\workbench_moderation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state transition entities.
 */
interface ModerationStateTransitionInterface extends ConfigEntityInterface {

  /**
   * Gets the from state for the given transition.
   *
   * @return string
   *   The moderation state ID for the from state.
   */
  public function getFromState();

  /**
   * Gets the to state for the given transition.
   *
   * @return string
   *   The moderation state ID for the to state.
   */
  public function getToState();

  /**
   * Gets the weight for the given transition.
   *
   * @return int
   *   The weight of this transition.
   */
  public function getWeight();

  /**
   * Sets the moderation state config prefix.
   *
   * @param string $moderation_state_config_prefix
   *   Moderation state config prefix.
   *
   * @return self
   *   Called instance.
   */
  public function setModerationStateConfigPrefix($moderation_state_config_prefix);

}
