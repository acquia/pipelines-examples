<?php

/**
 * @file
 * Contains scheduled_update.page.inc..
 *
 * Page callback for Scheduled update entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Scheduled update templates.
 *
 * Default template: scheduled_update.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_scheduled_update(array &$variables) {
  // Fetch ScheduledUpdate Entity Object.
  $scheduled_update = $variables['elements']['#scheduled_update'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
* Prepares variables for a custom entity type creation list templates.
*
* Default template: scheduled_update-content-add-list.html.twig.
*
* @param array $variables
*   An associative array containing:
*   - content: An array of scheduled_update-types.
*
* @see block_content_add_page()
*/
function template_preprocess_scheduled_update_content_add_list(&$variables) {
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => \Drupal::l($type->label(), new \Drupal\Core\Url('entity.scheduled_update.add_form', array(
        'scheduled_update_type' => $type->id()
      ), array('query' => $query))),
      'description' => array(
      '#markup' => $type->label(),
      ),
      'title' => $type->label(),
      'localized_options' => array(
      'query' => $query,
      ),
    );
  }
}
