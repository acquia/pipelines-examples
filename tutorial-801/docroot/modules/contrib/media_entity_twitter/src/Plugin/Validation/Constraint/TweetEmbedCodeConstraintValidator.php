<?php

namespace Drupal\media_entity_twitter\Plugin\Validation\Constraint;

use Drupal\media_entity\EmbedCodeValueTrait;
use Drupal\media_entity_twitter\Plugin\MediaEntity\Type\Twitter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TweetEmbedCode constraint.
 */
class TweetEmbedCodeConstraintValidator extends ConstraintValidator {

  use EmbedCodeValueTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $value = $this->getEmbedCode($value);
    if (!isset($value)) {
      return;
    }

    foreach (Twitter::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $value)) {
        return;
      }
    }

    $this->context->addViolation($constraint->message);
  }

}
