<?php

namespace Drupal\media_entity_twitter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\media_entity_twitter\Exception\TwitterApiException;

/**
 * Fetches (and caches) tweet data from Twitter's API.
 */
class TweetFetcher implements TweetFetcherInterface {

  /**
   * The optional cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The current set of Twitter API credentials.
   *
   * @var array
   */
  protected $credentials = [];

  /**
   * The current API exchange object.
   *
   * @var \TwitterAPIExchange
   */
  protected $twitter;

  /**
   * TweetFetcher constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface|NULL $cache
   *   (optional) A cache bin for storing fetched tweets.
   */
  public function __construct(CacheBackendInterface $cache = NULL) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchTweet($id) {
    // Tweets don't change much, so pull it out of the cache (if we have one)
    // if this one has already been fetched.
    if ($this->cache && $cached_tweet = $this->cache->get($id)) {
      return $cached_tweet->data;
    }

    // Ensure that we have an actual API exchange instance.
    if (empty($this->twitter)) {
      throw new \UnexpectedValueException('Twitter API exchange has not been initialized; credentials may not have been set yet.');
    }

    // Query Twitter's API.
    $response = $this->twitter
      ->setGetfield('?id=' . $id)
      ->buildOAuth('https://api.twitter.com/1.1/statuses/show.json', 'GET')
      ->performRequest();

    if (empty($response)) {
      throw new \Exception("Could not retrieve tweet $id.");
    }
    // Handle errors as per https://dev.twitter.com/overview/api/response-codes.
    if (!empty($response['errors'])) {
      throw new TwitterApiException($response['errors']);
    }

    $response = Json::decode($response);
    // If we have a cache, store the response for future use.
    if ($this->cache) {
      // Tweets don't change often, so the response should expire from the cache
      // on its own in 90 days.
      $this->cache->set($id, $response, time() + (86400 * 90));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getCredentials() {
    return $this->credentials;
  }

  /**
   * {@inheritdoc}
   */
  public function setCredentials($consumer_key, $consumer_secret, $oauth_access_token, $oauth_access_token_secret) {
    $this->credentials = [
      'consumer_key' => $consumer_key,
      'consumer_secret' => $consumer_secret,
      'oauth_access_token' => $oauth_access_token,
      'oauth_access_token_secret' => $oauth_access_token_secret,
    ];
    $this->twitter = new \TwitterAPIExchange($this->credentials);
  }

}
