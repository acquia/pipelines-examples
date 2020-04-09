<?php

namespace Drupal\media_entity_twitter\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid Tweet embed code/URL.
 *
 * @Constraint(
 *   id = "TweetEmbedCode",
 *   label = @Translation("Tweet embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class TweetEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Tweet URL/embed code.';

}
