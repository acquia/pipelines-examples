<?php

namespace Drupal\workbench_moderation;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\Entity\Node;

/**
 * Service to determine whether a route is the "Latest version" tab of a node.
 */
class WorkbenchPreprocess {

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   Current route match service.
   */
  public function __construct(CurrentRouteMatch $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Wrapper for hook_preprocess_HOOK().
   *
   * @param array $variables
   *   Theme variables to preprocess.
   */
  public function preprocessNode(array &$variables) {
    // Set the 'page' template variable when the node is being displayed on the
    // "Latest version" tab provided by workbench_moderation.
    $variables['page'] = $variables['page'] || $this->isLatestVersionPage($variables['node']);
  }

  /**
   * Checks whether a route is the "Latest version" tab of a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node.
   *
   * @return bool
   *  True if the current route is the latest version tab of the given node.
   */
  public function isLatestVersionPage(Node $node) {
    return $this->routeMatch->getRouteName() == 'entity.node.latest_version'
           && ($pageNode = $this->routeMatch->getParameter('node'))
           && $pageNode->id() == $node->id();
  }

}
