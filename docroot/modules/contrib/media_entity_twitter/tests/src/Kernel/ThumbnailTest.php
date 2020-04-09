<?php

namespace Drupal\Tests\media_entity_twitter\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity_twitter\Plugin\MediaEntity\Type\Twitter;
use Drupal\media_entity_twitter\TweetFetcherInterface;

/**
 * Tests SVG thumbnail generation from Twitter API responses.
 *
 * @group media_entity_twitter
 */
class ThumbnailTest extends KernelTestBase {

  /**
   * The mocked tweet fetcher.
   *
   * @var TweetFetcherInterface
   */
  protected $tweetFetcher;

  /**
   * The plugin under test.
   *
   * @var Twitter
   */
  protected $plugin;

  /**
   * A tweet media entity.
   *
   * @var MediaInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'file',
    'image',
    'media_entity',
    'media_entity_twitter',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installConfig(['media_entity_twitter', 'system']);

    $this->tweetFetcher = $this->getMock(TweetFetcherInterface::class);
    $this->container->set('media_entity_twitter.tweet_fetcher', $this->tweetFetcher);

    MediaBundle::create([
      'id' => 'tweet',
      'type' => 'twitter',
      'type_configuration' => [
        'source_field' => 'tweet',
        'use_twitter_api' => TRUE,
        'consumer_key' => $this->randomString(),
        'consumer_secret' => $this->randomString(),
        'oauth_access_token' => $this->randomString(),
        'oauth_access_token_secret' => $this->randomString(),
      ],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'tweet',
      'entity_type' => 'media',
      'type' => 'string_long',
    ])->save();

    FieldConfig::create([
      'field_name' => 'tweet',
      'entity_type' => 'media',
      'bundle' => 'tweet',
    ])->save();

    $this->entity = Media::create([
      'bundle' => 'tweet',
      'tweet' => 'https://twitter.com/foobar/status/12345',
    ]);

    $this->plugin = Twitter::create(
      $this->container,
      MediaBundle::load('tweet')->getTypeConfiguration(),
      'twitter',
      []
    );

    $dir = $this->container
      ->get('config.factory')
      ->get('media_entity_twitter.settings')
      ->get('local_images');

    file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  }

  /**
   * Tests that an existing local image is used as the thumbnail.
   */
  public function testLocalImagePresent() {
    $this->tweetFetcher
      ->method('fetchTweet')
      ->willReturn([
        'extended_entities' => [
          'media' => [
            [
              'media_url' => 'https://drupal.org/favicon.ico',
            ],
          ],
        ],
      ]);

    $uri = 'public://twitter-thumbnails/12345.ico';
    touch($uri);
    $this->assertEquals($uri, $this->plugin->thumbnail($this->entity));
  }

  /**
   * Tests that a local image is downloaded if available but not present.
   */
  public function testLocalImageNotPresent() {
    $this->tweetFetcher
      ->method('fetchTweet')
      ->willReturn([
        'extended_entities' => [
          'media' => [
            [
              'media_url' => 'https://drupal.org/favicon.ico',
            ],
          ],
        ],
      ]);

    $this->plugin->thumbnail($this->entity);
    $this->assertFileExists('public://twitter-thumbnails/12345.ico');
  }

  /**
   * Tests that the default thumbnail is used if no local image is available.
   */
  public function testNoLocalImage() {
    $this->assertEquals(
      $this->plugin->getDefaultThumbnail(),
      $this->plugin->thumbnail($this->entity)
    );
  }

  /**
   * Tests that thumbnail is generated if enabled and local image not available.
   */
  public function testThumbnailGeneration() {
    $configuration = $this->plugin->getConfiguration();
    $configuration['generate_thumbnails'] = TRUE;
    $this->plugin->setConfiguration($configuration);

    $uri = $this->plugin->thumbnail($this->entity);
    $this->assertFileExists($uri);
  }

}
