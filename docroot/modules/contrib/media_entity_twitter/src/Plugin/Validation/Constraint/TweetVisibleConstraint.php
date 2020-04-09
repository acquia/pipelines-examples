<?php

namespace Drupal\media_entity_twitter\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a Tweet is publicly visible.
 *
 * @Constraint(
 *   id = "TweetVisible",
 *   label = @Translation("Tweet publicly visible", context = "Validation"),
 *   type = { "entity", "entity_reference", "string", "string_long" }
 * )
 */
class TweetVisibleConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Referenced tweet is not publicly visible.';

}
