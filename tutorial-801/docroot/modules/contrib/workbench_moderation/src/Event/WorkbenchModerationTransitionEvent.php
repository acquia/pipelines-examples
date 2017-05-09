<?php

namespace Drupal\workbench_moderation\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * @see \Drupal\workbench_moderation\ModerationStateEvents
 */
class WorkbenchModerationTransitionEvent extends Event {

  /**
   * The entity which was changed.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * @var string
   */
  protected $stateBefore;

  /**
   * @var string
   */
  protected $stateAfter;

  /**
   * Creates a new WorkbenchModerationTransitionEvent instance.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity which was changed.
   * @param string $state_before
   *   The state before the transition.
   * @param string $state_after
   *   The state after the transition.
   */
  public function __construct(ContentEntityInterface $entity, $state_before, $state_after) {
    $this->entity = $entity;
    $this->stateBefore = $state_before;
    $this->stateAfter = $state_after;
  }

  /**
   * Returns the changed entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return string
   */
  public function getStateBefore() {
    return $this->stateBefore;
  }

  /**
   * @return string
   */
  public function getStateAfter() {
    return $this->stateAfter;
  }

}
