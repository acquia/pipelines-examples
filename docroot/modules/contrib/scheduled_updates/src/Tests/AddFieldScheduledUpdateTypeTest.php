<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Tests\AddFieldScheduledUpdateTypeTest.
 */


namespace Drupal\scheduled_updates\Tests;
use Drupal\scheduled_updates\Plugin\UpdateRunnerInterface;

/**
 * Test adding an Embedded Scheduled Update Type via Manage Field page.
 *
 * @group scheduled_updates
 */
class AddFieldScheduledUpdateTypeTest extends EmbeddedScheduledUpdateTypeTestBase {

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'administer content types',
    'administer nodes',
    'administer scheduled update types',
    'administer scheduled_update fields',
    'administer scheduled_update form display',
    'administer node fields',
    'administer content types',
    'administer node form display',
  ];

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }


  public function testAddUpdateFields() {
    $this->checkAddUpdateField('page', 'title', 'Title');

    $clone_field_options = [
      'default_value_input[_no_form_display]' => 1,
      'default_value_input[promote][value]' => 1,
    ];
    $this->checkAddUpdateField('page', 'promote', 'Promoted to front page', $clone_field_options, TRUE);
  }
  /**
   * Test to check from manage fields on Node.
   */
  protected function checkAddUpdateField ($bundle, $clone_field, $clone_field_label, $clone_field_options = [], $other_reference_exists = FALSE) {
    $this->drupalGet("admin/structure/types/manage/$bundle/fields");
    $this->assertLink('Add Update field');
    $add_url = "admin/structure/types/manage/$bundle/fields/add-scheduled-update";
    $this->assertLinkByHref($add_url);
    $this->clickLink('Add Update field');
    $this->assertUrl("admin/structure/types/manage/$bundle/fields/add-scheduled-update");
    $this->assertText('Update Field');
    $this->checkRunnersAvailable(['default_embedded', 'latest_revision']);
    $this->checkNodeProperties();



    $label = 'Foo Type';

    $this->checkExpectedOptions(
      'edit-update-runner-update-user',
      [
        UpdateRunnerInterface::USER_UPDATE_RUNNER,
        UpdateRunnerInterface::USER_UPDATE_OWNER,
        UpdateRunnerInterface::USER_OWNER,
        UpdateRunnerInterface::USER_REVISION_OWNER,
      ]
    );
    $edit = [
      'clone_field' => $clone_field,
      'update_runner[id]' => 'default_embedded',
      'update_runner[after_run]' => UpdateRunnerInterface::AFTER_DELETE,
      'update_runner[invalid_update_behavior]' => UpdateRunnerInterface::INVALID_DELETE,
      'update_runner[update_user]' => UpdateRunnerInterface::USER_UPDATE_RUNNER,
      'update_runner[create_revisions]' => UpdateRunnerInterface::REVISIONS_BUNDLE_DEFAULT,
      "reference_settings[bundles][$bundle]" => $bundle,
    ];
    if ($clone_field_options) {
      $this->drupalPostAjaxForm(NULL,
        $edit,
        'clone_field'
      );
      $edit += $clone_field_options;

    }
    if ($other_reference_exists) {
      $this->assertText('Reference Field Options');
      $edit['reference_settings[reference_field_options]'] = 'new';
    }
    else {
      $this->assertNoText('Reference Field Options');

    }
    $reference_field_label = "Reference: $clone_field_label";
    $reference_field_name = $clone_field . '_reference';
    $this->checkNewFieldRequired($edit, $add_url, $reference_field_label, $reference_field_name);

    // Save a second time to redirect to clone page.
    $this->drupalPostForm(NULL,
      $edit,
      t('Save')
    );
    $this->assertUrl("admin/structure/types/manage/$bundle/form-display");

    $type_id = 'node__' . $clone_field;
    $type_label = 'Content - ' . $clone_field_label;
    $this->assertText("Created the $type_label Scheduled Update Type.");



    $this->drupalGet('admin/config/workflow/scheduled-update-type/list');
    $this->assertText($type_id);
    $this->assertText($type_label);

    $this->checkAfterTypeCreated($label, $type_id, $reference_field_label, $reference_field_name, $clone_field);
  }


}
