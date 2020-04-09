<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Tests\EmbeddedScheduledUpdateTypeTest.
 */


namespace Drupal\scheduled_updates\Tests;
use Drupal\scheduled_updates\Plugin\UpdateRunnerInterface;


/**
 * Test adding an Embedded Scheduled Update Type.
 *
 * @group scheduled_updates
 */
class EmbeddedScheduledUpdateTypeTest extends EmbeddedScheduledUpdateTypeTestBase {

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  public function testCreateType() {
    $type_id = 'foo';
    $label = 'Foo Type';
    $clone_fields = [
      'base_fields[title]' => [
        'input_value' => 'title',
        'label' => t('Title'),
      ]
    ];

    $this->createType($label, $type_id, $clone_fields);
  }

  /**
   * Create a scheduled update type via the UI.
   *
   * @param $label
   * @param $type_id
   * @param array $clone_fields
   * @param array $type_options
   *
   * @throws \Exception
   */
  protected function createType($label, $type_id, array $clone_fields, $type_options = []) {
    $add_url = 'admin/config/workflow/scheduled-update-type/add';
    $this->drupalGet($add_url);
    // Revision options should not be displayed until entity type that supports it is selected.
    $this->assertNoText('The owner of the last revision.');
    $this->assertNoText('Create New Revisions');
    $edit = $type_options + [
        'label' => $label,
        'id' => $type_id,
        'update_entity_type' => 'node',
        'update_runner[id]' => 'default_embedded',
        'update_runner[after_run]' => UpdateRunnerInterface::AFTER_DELETE,
        'update_runner[invalid_update_behavior]' => UpdateRunnerInterface::INVALID_DELETE,
        'update_runner[update_user]' => UpdateRunnerInterface::USER_UPDATE_RUNNER,

      ];

    $this->checkRunnersAvailable();
    $this->drupalPostAjaxForm(NULL, $edit, 'update_entity_type');

    $this->assertText('The owner of the last revision.');
    $this->assertText('Create New Revisions');
    $edit = $type_options + [
      'label' => $label,
      'id' => $type_id,
      'clone_field' => 'multiple-field',
      'update_entity_type' => 'node',
      'update_runner[id]' => 'default_embedded',
      'update_runner[after_run]' => UpdateRunnerInterface::AFTER_DELETE,
      'update_runner[invalid_update_behavior]' => UpdateRunnerInterface::INVALID_DELETE,
      'update_runner[update_user]' => UpdateRunnerInterface::USER_UPDATE_RUNNER,
      'update_runner[create_revisions]' => UpdateRunnerInterface::REVISIONS_YES,
      'reference_settings[bundles][article]' => 'article',
      'reference_settings[bundles][page]' => 'page',
    ];

    $reference_field_label = 'Reference Label';
    $reference_field_name = 'update_reference';
    $this->checkNewFieldRequired($edit, $add_url, $reference_field_label, $reference_field_name);
    // Save a second time to redirect to clone page.
    $this->drupalPostForm(NULL,
      $edit,
      t('Save')
    );
    $this->assertUrl("admin/config/workflow/scheduled-update-type/$type_id/clone-fields");
    $this->assertText("Created the $label Scheduled Update Type.");
    $this->assertText("Select fields to add to these updates");
    $this->checkNodeProperties();
    // @todo test that node.body displays and is select field.

    $this->cloneFields($type_id, $clone_fields);
    $this->assertUrl("admin/config/workflow/scheduled-update-type/$type_id/form-display", [], 'Redirect to form display after field clone.');
    $this->assertText('The fields have been created and mapped.');
    $this->assertNoText('Entities to Update');

    $this->checkAfterTypeCreated($label, $type_id, $reference_field_label, $reference_field_name, 'title');

  }



}
