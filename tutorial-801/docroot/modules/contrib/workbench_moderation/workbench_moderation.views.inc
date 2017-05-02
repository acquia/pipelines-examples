<?php

/**
 * Implements hook_views_data().
 */
function workbench_moderation_views_data() {
  /** @var \Drupal\workbench_moderation\ViewsData $workbench_moderation_views_data */
  $workbench_moderation_views_data = \Drupal::service('workbench_moderation.views_data');
  return $workbench_moderation_views_data->getViewsData();
}
