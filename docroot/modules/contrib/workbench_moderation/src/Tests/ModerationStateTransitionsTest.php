<?php

namespace Drupal\workbench_moderation\Tests;

/**
 * Tests moderation state transition config entity.
 *
 * @group workbench_moderation
 */
class ModerationStateTransitionsTest extends ModerationStateTestBase {

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    $paths = [
      'admin/structure/workbench-moderation/transitions',
      'admin/structure/workbench-moderation/transitions/add',
      'admin/structure/workbench-moderation/transitions/draft_needs_review',
      'admin/structure/workbench-moderation/transitions/draft_needs_review/delete',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      // No access.
      $this->assertResponse(403);
    }
    $this->drupalLogin($this->adminUser);
    foreach ($paths as $path) {
      $this->drupalGet($path);
      // User has access.
      $this->assertResponse(200);
    }
  }

  /**
   * Tests administration of moderation state transition entity.
   */
  public function testTransitionAdministration() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/workbench-moderation');
    $this->clickLink('Moderation state transitions');
    $this->assertLink('Add Moderation state transition');
    $this->assertText('Request Review');

    // Edit the Draft » Needs review.
    $this->drupalGet('admin/structure/workbench-moderation/transitions/draft_needs_review');
    $this->assertFieldByName('label', 'Request Review');
    $this->assertFieldByName('stateFrom', 'draft');
    $this->assertFieldByName('stateTo', 'needs_review');
    $this->drupalPostForm(NULL, [
      'label' => 'Draft to Needs review',
    ], t('Save'));
    $this->assertText('Saved the Draft to Needs review Moderation state transition.');
    $this->drupalGet('admin/structure/workbench-moderation/transitions/draft_needs_review');
    $this->assertFieldByName('label', 'Draft to Needs review');
    // Now set it back.
    $this->drupalPostForm(NULL, [
      'label' => 'Request Review',
    ], t('Save'));
    $this->assertText('Saved the Request Review Moderation state transition.');

    // Add a new state.
    $this->drupalGet('admin/structure/workbench-moderation/states/add');
    $this->drupalPostForm(NULL, [
      'label' => 'Expired',
      'id' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Expired Moderation state.');

    // Add a new transition.
    $this->drupalGet('admin/structure/workbench-moderation/transitions');
    $this->clickLink(t('Add Moderation state transition'));
    $this->drupalPostForm(NULL, [
      'label' => 'Published » Expired',
      'id' => 'published_expired',
      'stateFrom' => 'published',
      'stateTo' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Published » Expired Moderation state transition.');

    // Delete the new transition.
    $this->drupalGet('admin/structure/workbench-moderation/transitions/published_expired');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete Published » Expired?');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('Moderation transition Published » Expired deleted');
  }

}
