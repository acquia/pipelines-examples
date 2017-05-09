<?php

namespace Drupal\workbench_moderation\Event;

final class WorkbenchModerationEvents {

  /**
   * This event is fired everytime a state is changed.
   *
   * @Event
   */
  const STATE_TRANSITION = 'workbench_moderation.state_transition';

}
