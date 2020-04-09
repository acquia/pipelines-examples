<?php

namespace Drupal\media_entity_instagram\Plugin\Validation\Constraint;

use Drupal\media_entity\EmbedCodeValueTrait;
use Drupal\media_entity_instagram\Plugin\MediaEntity\Type\Instagram;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the InstagramEmbedCode constraint.
 */
class InstagramEmbedCodeConstraintValidator extends ConstraintValidator {

  use EmbedCodeValueTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $value = $this->getEmbedCode($value);
    if (!isset($value)) {
      return;
    }

    $matches = [];
    foreach (Instagram::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $value, $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    if (empty($matches)) {
      $this->context->addViolation($constraint->message);
    }
  }

}
