<?php

namespace Drupal\media_entity_instagram\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid Instagram embed code/URL.
 *
 * @constraint(
 *   id = "InstagramEmbedCode",
 *   label = @Translation("Instagram embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class InstagramEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Instagram URL/Embed code.';

}
