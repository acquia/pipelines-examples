<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\comment\CommentInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\UserInterface;

/**
 * Excludes unpublished nodes from node indexes.
 *
 * @SearchApiProcessor(
 *   id = "entity_status",
 *   label = @Translation("Entity status"),
 *   description = @Translation("Exclude unpublished content, unpublished comments and inactive users from being indexed."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class EntityStatus extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $supported_entity_types = ['node', 'comment', 'user'];
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), $supported_entity_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      // @todo Use EntityPublishedInterface once we depend on Drupal 8.3+.
      $enabled = TRUE;
      if ($object instanceof NodeInterface) {
        $enabled = $object->isPublished();
      }
      elseif ($object instanceof CommentInterface) {
        $enabled = $object->isPublished();
      }
      elseif ($object instanceof UserInterface) {
        $enabled = $object->isActive();
      }
      if (!$enabled) {
        unset($items[$item_id]);
      }
    }
  }

}
