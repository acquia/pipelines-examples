<?php

namespace Drupal\search_api_views_taxonomy\Plugin\views\filter;

use Drupal\search_api\Plugin\views\filter\SearchApiFilterTrait;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * Defines a filter for filtering on taxonomy term references.
 *
 * Based on \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_term")
 */
class SearchApiTerm extends TaxonomyIndexTid {

  use SearchApiFilterTrait;

}
