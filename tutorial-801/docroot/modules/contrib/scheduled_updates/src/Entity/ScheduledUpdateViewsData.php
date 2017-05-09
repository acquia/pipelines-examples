<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Entity\ScheduledUpdate.
 */

namespace Drupal\scheduled_updates\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Scheduled update entities.
 */
class ScheduledUpdateViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['scheduled_update']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Scheduled update'),
      'help' => $this->t('The Scheduled update ID.'),
    );

    return $data;
  }

}
