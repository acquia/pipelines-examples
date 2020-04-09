<?php

/**
 * @file
 * Document all supported APIs.
 */

/**
 * Provides a ability to integrate alternative routes with metatags.
 *
 * Return an entity when the given route/route parameters matches a certain
 * entity. All metatags will be rendered on that page.
 *
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The route match.
 *
 * @return \Drupal\Core\Entity\EntityInterface|null
 *   Return an entity, if the route should use metatags.
 */
function hook_metatag_route_entity(\Drupal\Core\Routing\RouteMatchInterface $route_match) {
  if ($route_match->getRouteName() === 'example.test_route') {
    if ($node = $route_match->getParameter('node')) {
      return $node;
    }
  }
}
