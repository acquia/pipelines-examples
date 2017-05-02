<?php

namespace Drupal\media_entity_twitter;

/**
 * Defines a wrapper around the Twitter API.
 */
interface TweetFetcherInterface {

  /**
   * Retrieves a tweet by its ID.
   *
   * @param int $id
   *   The tweet ID.

   * @return array
   *   The tweet information.
   *
   * @throws \Drupal\media_entity_twitter\Exception\TwitterApiException
   *   If the Twitter API returns errors in the response.
   */
  public function fetchTweet($id);

  /**
   * Returns the current Twitter API credentials.
   *
   * @return array
   *   The API credentials. Will be an array with consumer_key, consumer_secret,
   *   oauth_access_token, and oauth_access_token_secret elements.
   */
  public function getCredentials();

  /**
   * Sets the credentials for accessing Twitter's API.
   *
   * @param string $consumer_key
   *   The consumer key.
   * @param $consumer_secret
   *   The consumer secret.
   * @param string $oauth_access_token
   *   The OAuth access token.
   * @param string $oauth_access_token_secret
   *   The OAuth access token secret.
   */
  public function setCredentials($consumer_key, $consumer_secret, $oauth_access_token, $oauth_access_token_secret);

}
