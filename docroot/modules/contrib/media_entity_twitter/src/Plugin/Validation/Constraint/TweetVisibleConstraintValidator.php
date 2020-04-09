<?php

namespace Drupal\media_entity_twitter\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\media_entity\EmbedCodeValueTrait;
use Drupal\media_entity_twitter\Plugin\MediaEntity\Type\Twitter;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TweetVisible constraint.
 */
class TweetVisibleConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use EmbedCodeValueTrait;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new TweetVisibleConstraintValidator.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The http client service.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $value = $this->getEmbedCode($value);
    if (!isset($value)) {
      return;
    }

    $matches = [];

    foreach (Twitter::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $value, $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    if (empty($matches[0][0])) {
      // If there are no matches the URL is not correct, so stop validation.
      return;
    }

    // Fetch content from the given url.
    $response = $this->httpClient->get($matches[0][0], ['allow_redirects' => FALSE]);

    if ($response->getStatusCode() == 302 && ($location = $response->getHeader('location'))) {
      $effective_url_parts = parse_url($location[0]);
      if (!empty($effective_url_parts) && isset($effective_url_parts['query']) && $effective_url_parts['query'] == 'protected_redirect=true') {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
