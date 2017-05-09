<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Controller\ScheduledUpdateTypeController.
 */


namespace Drupal\scheduled_updates\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\scheduled_updates\ScheduledUpdateTypeInterface;

/**
 * Controller for Scheduled Update Types.
 */
class ScheduledUpdateTypeController extends ControllerBase{
  public function editTitle(ScheduledUpdateTypeInterface $scheduled_update_type) {
    return $this->t('Edit <em>@label</em> Scheduled Update Type', ['@label' => $scheduled_update_type->label()]);
  }
}
