<?php

namespace Drupal\media_entity_twitter\Exception;

/**
 * Exception thrown when Twitter's API returns errors in a response.
 */
class TwitterApiException extends \Exception {

  /**
   * TwitterApiException constructor.
   *
   * @param array $errors
   *   The errors returned from Twitter's API. Each error contains 'message'
   *   and 'code' elements.
   * @param int $code
   *   (optional) The general error code for the exception.
   * @param \Exception|NULL $previous
   *   (optional) The previous exception.
   *
   * @see https://dev.twitter.com/overview/api/response-codes
   */
  public function __construct(array $errors, $code = 0, \Exception $previous = NULL) {
    $errors = array_map(
      function (array $error) {
        return sprintf('[%d] %s', $error['code'], $error['message']);
      },
      $errors
    );

    array_unshift($errors, 'Twitter API returned error(s):');
    $errors = implode("\n", $errors);

    parent::__construct($errors, $code, $previous);
  }

}
