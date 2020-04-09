<?php

namespace Drupal\workbench_moderation;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Entity\ModerationStateTransition;

/**
 * Defines a class for dynamic permissions based on transitions.
 */
class Permissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of transition permissions.
   *
   * @return array
   *   The transition permissions.
   */
  public function transitionPermissions() {
    // @todo write a test for this.
    $perms = [];
    /* @var \Drupal\workbench_moderation\ModerationStateInterface[] $states */
    $states = ModerationState::loadMultiple();
    /* @var \Drupal\workbench_moderation\ModerationStateTransitionInterface $transition */
    foreach (ModerationStateTransition::loadMultiple() as $id => $transition) {
      $perms['use ' . $id . ' transition'] = [
        'title' => $this->t('Use the %transition_name transition', [
          '%transition_name' => $transition->label(),
        ]),
        'description' => $this->t('Move content from %from state to %to state.', [
          '%from' => $states[$transition->getFromState()]->label(),
          '%to' => $states[$transition->getToState()]->label(),
        ]),
      ];
    }

    return $perms;
  }

}
