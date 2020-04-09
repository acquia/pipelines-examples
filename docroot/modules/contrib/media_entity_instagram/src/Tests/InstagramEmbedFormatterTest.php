<?php

namespace Drupal\media_entity_instagram\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\media_entity\Tests\MediaTestTrait;

/**
 * Tests for Instagram embed formatter.
 *
 * @group media_entity_instagram
 */
class InstagramEmbedFormatterTest extends WebTestBase {

  use MediaTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'media_entity_instagram',
    'media_entity',
    'node',
    'field_ui',
    'views_ui',
    'block',
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
  protected $mediaId = 'instagram';

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
    $this->testBundle = $this->drupalCreateMediaBundle($bundle, 'instagram');
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
   * Tests adding and editing an instagram embed formatter.
   */
  public function testManageFieldFormatter() {
    // Test and create one media bundle.
    $bundle = $this->testBundle;

    // Assert that the media bundle has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('type', 'instagram');

    // Add and save field settings (Embed code).
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

    // Set the new field as required.
    $edit = [
      'required' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertText('Saved ' . $edit_conf['label'] . ' configuration.');

    // Assert that the new field configuration has been successfully saved.
    $xpath = $this->xpath('//*[@id="field-embed-code"]');
    $this->assertEqual((string) $xpath[0]->td[0], 'Embed code');
    $this->assertEqual((string) $xpath[0]->td[1], 'field_embed_code');
    $this->assertEqual((string) $xpath[0]->td[2]->a, 'Text (plain, long)');

    // Test if edit worked and if new field values have been saved as
    // expected.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('type', 'instagram');
    $this->assertFieldByName('type_configuration[instagram][source_field]', 'field_embed_code');
    $this->drupalPostForm(NULL, NULL, t('Save media bundle'));
    $this->assertText('The media bundle ' . $bundle->label() . ' has been updated.');
    $this->assertText($bundle->label());

    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/display');

    // Set and save the settings of the new field.
    $edit = [
      'fields[field_embed_code][label]' => 'above',
      'fields[field_embed_code][type]' => 'instagram_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // Create and save the media with an instagram media code.
    $this->drupalGet('media/add/' . $bundle->id());

    // Random image from instagram.
    $instagram = '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="7" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:62.4537037037% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAMUExURczMzPf399fX1+bm5mzY9AMAAADiSURBVDjLvZXbEsMgCES5/P8/t9FuRVCRmU73JWlzosgSIIZURCjo/ad+EQJJB4Hv8BFt+IDpQoCx1wjOSBFhh2XssxEIYn3ulI/6MNReE07UIWJEv8UEOWDS88LY97kqyTliJKKtuYBbruAyVh5wOHiXmpi5we58Ek028czwyuQdLKPG1Bkb4NnM+VeAnfHqn1k4+GPT6uGQcvu2h2OVuIf/gWUFyy8OWEpdyZSa3aVCqpVoVvzZZ2VTnn2wU8qzVjDDetO90GSy9mVLqtgYSy231MxrY6I2gGqjrTY0L8fxCxfCBbhWrsYYAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <a href="https://www.instagram.com/p/BGwoSD1hQQw/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Weekend Hashtag Project: #WHParchitecture The goal this weekend is to photograph architecture, and will be curated by Shoair Mavlian (@shoair_m), photography curator of the Tate Modern museum (@tate) in London, which is celebrating the opening of its new building, the Switch House, today. “Architecture is present everywhere in our everyday surroundings — from cutting-edge museum buildings and towering glass office blocks to bus stops, schools and sports stadiums,” says Shoair. “The world looks very different if we stop, rethink (and photograph!) the everyday examples which surround us.” Here is how Shoair says to get started: Focus on the different ways buildings and landmarks affect you. “Architectural structures play an important role in our everyday lives, from the private spaces we share with family to communal spaces we work or study in to shared public spaces we use to gather and come together,” says Shoair. Step back to show buildings within the context of their landscapes and surroundings (whether a skyscraper towering over an urban skyline or a thatched-roof house in the middle of an open field). Don’t forget to go inside the spaces you photograph to capture interesting details in walls, doorways and stairwells — as well as the people that inhabit or utilize the space. PROJECT RULES: Please add the #WHParchitecture hashtag only to photos and videos taken over this weekend and only submit your own visuals to the project. If you include music in your submissions, please only use music to which you own the rights. Any tagged visual taken over the weekend is eligible to be featured next week. Photo of @tate by @freepy</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A photo posted by Instagram (@instagram) on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="2016-06-17T15:00:09+00:00">Jun 17, 2016 at 8:00am PDT</time></p></div></blockquote> <script async defer src="//platform.instagram.com/en_US/embeds.js"></script>';

    $edit = [
      'name[0][value]' => 'Title',
      'field_embed_code[0][value]' => $instagram,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Assert that the media has been successfully saved.
    $this->assertText('Title');
    $this->assertText('Embed code');

    // Assert that the formatter exists on this page.
    $this->assertFieldByXPath('//iframe');
  }

}
