<?php

namespace Drupal\media_entity_twitter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity_twitter\Plugin\MediaEntity\Type\Twitter;

/**
 * Plugin implementation of the 'twitter_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "twitter_embed",
 *   label = @Translation("Twitter embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class TwitterEmbedFormatter extends FormatterBase {

  /**
   * Extracts the embed code from a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string|null
   *   The embed code, or NULL if the field type is not supported.
   */
  protected function getEmbedCode(FieldItemInterface $item) {
    switch ($item->getFieldDefinition()->getType()) {
      case 'link':
        return $item->uri;

      case 'string':
      case 'string_long':
        return $item->value;

      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $matches = [];

      foreach (Twitter::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $this->getEmbedCode($item), $item_matches)) {
          $matches[] = $item_matches;
        }
      }

      if (!empty($matches)) {
        $matches = reset($matches);
      }

      if (!empty($matches['user']) && !empty($matches['id'])) {
        $element[$delta] = [
          '#theme' => 'media_entity_twitter_tweet',
          '#path' => 'https://twitter.com/' . $matches['user'] . '/statuses/' . $matches['id'],
          '#attributes' => [
            'class' => ['twitter-tweet', 'element-hidden'],
            'data-conversation' => 'none',
            'lang' => 'en',
          ],
        ];
      }
    }

    if (!empty($element)) {
      $element['#attached'] = [
        'library' => [
          'media_entity_twitter/integration',
        ],
      ];
    }

    return $element;
  }

}
