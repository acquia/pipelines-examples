<?php

namespace Drupal\media_entity_twitter\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\media_entity\Tests\MediaTestTrait;

/**
 * Tests for Twitter embed formatter.
 *
 * @group media_entity_twitter
 */
class TweetEmbedFormatterTest extends WebTestBase {

  use MediaTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'media_entity_twitter',
    'media_entity',
    'node',
    'field_ui',
    'views_ui',
    'block',
    'link',
  );

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * Media entity machine id.
   *
   * @var string
   */
  protected $mediaId = 'twitter';

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle['bundle'] = $this->mediaId;
    $this->testBundle = $this->drupalCreateMediaBundle($bundle, 'twitter');
    $this->drupalPlaceBlock('local_actions_block');
    $this->adminUser = $this->drupalCreateUser([
      'administer media',
      'administer media bundles',
      'administer media fields',
      'administer media form display',
      'administer media display',
      // Media entity permissions.
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
      // Other permissions.
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests adding and editing a twitter embed formatter.
   */
  public function testManageEmbedFormatter() {
    // Test and create one media bundle.
    $bundle = $this->testBundle;

    // Assert that the media bundle has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('type', 'twitter');

    // Add and save link field type settings (Embed code).
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields/add-field');
    $edit_conf = [
      'new_storage_type' => 'link',
      'label' => 'Link URL',
      'field_name' => 'link_url',
    ];
    $this->drupalPostForm(NULL, $edit_conf, t('Save and continue'));
    $this->assertText('These settings apply to the ' . $edit_conf['label'] . ' field everywhere it is used.');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertText('Updated field ' . $edit_conf['label'] . ' field settings.');

    // Set the new link field type as required.
    $edit = [
      'required' => TRUE,
      'settings[link_type]' => '16',
      'settings[title]' => '0',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertText('Saved ' . $edit_conf['label'] . ' configuration.');

    // Add and save string_long field type settings (Embed code).
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields/add-field');
    $edit_conf = [
      'new_storage_type' => 'string_long',
      'label' => 'Embed code',
      'field_name' => 'embed_code',
    ];
    $this->drupalPostForm(NULL, $edit_conf, t('Save and continue'));
    $this->assertText('These settings apply to the ' . $edit_conf['label'] . ' field everywhere it is used.');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertText('Updated field ' . $edit_conf['label'] . ' field settings.');

    // Set the new string_long field type as required.
    $edit = [
      'required' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertText('Saved ' . $edit_conf['label'] . ' configuration.');

    // Assert that the new field types configurations have been successfully
    // saved.
    $xpath = $this->xpath('//*[@id="field-link-url"]');
    $this->assertEqual((string) $xpath[0]->td[0], 'Link URL');
    $this->assertEqual((string) $xpath[0]->td[1], 'field_link_url');
    $this->assertEqual((string) $xpath[0]->td[2]->a, 'Link');

    $xpath = $this->xpath('//*[@id="field-embed-code"]');
    $this->assertEqual((string) $xpath[0]->td[0], 'Embed code');
    $this->assertEqual((string) $xpath[0]->td[1], 'field_embed_code');
    $this->assertEqual((string) $xpath[0]->td[2]->a, 'Text (plain, long)');

    // Test if edit worked and if new fields values have been saved as
    // expected.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('type', 'twitter');
    $this->assertFieldByName('type_configuration[twitter][source_field]', 'field_embed_code');
    $this->drupalPostForm(NULL, NULL, t('Save media bundle'));
    $this->assertText('The media bundle ' . $bundle->label() . ' has been updated.');
    $this->assertText($bundle->label());

    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/display');

    // Set and save the settings of the new field types.
    $edit = [
      'fields[field_link_url][label]' => 'above',
      'fields[field_link_url][type]' => 'twitter_embed',
      'fields[field_embed_code][label]' => 'above',
      'fields[field_embed_code][type]' => 'twitter_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // Create and save the media with a twitter media code.
    $this->drupalGet('media/add/' . $bundle->id());

    // Random image url from twitter.
    $tweet_url = 'https://twitter.com/RamzyStinson/status/670650348319576064';

    // Random image from twitter.
    $tweet = '<blockquote class="twitter-tweet" lang="it"><p lang="en" dir="ltr">' .
             'Midnight project. I ain&#39;t got no oven. So I improvise making this milo crunchy kek batik. hahahaha ' .
             '<a href="https://twitter.com/hashtag/itssomething?src=hash">#itssomething</a> ' .
             '<a href="https://t.co/Nvn4Q1v2ae">pic.twitter.com/Nvn4Q1v2ae</a></p>&mdash; Zi (@RamzyStinson) ' .
             '<a href="https://twitter.com/RamzyStinson/status/670650348319576064">' .
             '28 Novembre 2015</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';

    $edit = [
      'name[0][value]' => 'Title',
      'field_link_url[0][uri]' => $tweet_url,
      'field_embed_code[0][value]' => $tweet,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Assert that the media has been successfully saved.
    $this->assertText('Title');

    // Assert that the link url formatter exists on this page.
    $this->assertText('Link URL');
    $this->assertRaw('<a href="https://twitter.com/RamzyStinson/statuses/670650348319576064">', 'Link in embedded Tweet found.');

    // Assert that the string_long code formatter exists on this page.
    $this->assertText('Embed code');
    $this->assertRaw('<blockquote class="twitter-tweet', 'Embedded Tweet found.');
  }

}
