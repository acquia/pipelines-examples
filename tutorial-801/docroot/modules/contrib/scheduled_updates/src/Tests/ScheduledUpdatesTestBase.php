<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Tests\ScheduledUpdatesTestBase.
 */

namespace Drupal\scheduled_updates\Tests;


use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\scheduled_updates\Plugin\UpdateRunnerInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;

/**
 * Define base class for Scheduled Updates Tests
 */
abstract class ScheduledUpdatesTestBase extends WebTestExtended {

  /**
   * Profile to use.
   */
  protected $profile = 'testing';

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

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
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'scheduled_updates',
    'node',
    'user',
    'field_ui',
    'block',
    'inline_entity_form'
  ];


  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);
    $this->setupContentTypes();
  }

  /**
   * Clone multiple fields on the Clone Field Page.
   *
   * @param $type_id
   * @param array $fields
   *
   * @throws \Exception
   */
  protected function cloneFields($type_id, array $fields) {
    $this->gotoURLIfNot("admin/config/workflow/scheduled-update-type/$type_id/clone-fields");
    $edit = [];
    foreach ($fields as $input_name => $field_info) {
      // Check the field label exists.
      $this->assertText(
        $field_info['label'],
        new FormattableMarkup('Field label %label displayed.', ['%label' => $field_info['label']])
      );
      // Add to post data.
      $edit[$input_name] = $field_info['input_value'];
    }
    $this->drupalPostForm(NULL, $edit, t('Clone Fields'));
    if ($this->adminUser->hasPermission('administer scheduled_update form display')) {
      // Should be redirected to form display after cloning fields
      $this->assertUrl("admin/config/workflow/scheduled-update-type/$type_id/form-display");
      $this->checkFieldLabels($fields);
    }
    else {
      // @todo Does it make any sense for admin to be able to add update types without Field UI permissions
      //  Enforce Field UI permissions to add scheduled update type?
      $this->assertText('You do not have permission to administer fields on Scheduled Updates.');
    }

  }

  /**
   * @param array $fields
   */
  protected function checkFieldLabels(array $fields) {
    foreach ($fields as $input_name => $field_info) {
      // We only know what base field labels should look like.
      if (stripos($input_name, 'base_fields[') === 0) {
        // Check the field label exists.
        $this->assertText(
          $field_info['label'],
          new FormattableMarkup('Field label %label displayed.', ['%label' => $field_info['label']])
        );
      }
      else {
        // @test that Configurable fields were cloned.
      }
    }
  }

  protected function checkNodeProperties() {
    $property_labels = $this->getNodePropertyLabels();
    foreach ($property_labels as $property_label) {
      $this->assertText($property_label);
    }

  }

  /**
   * @param $label
   * @param $id
   * @param array $clone_fields
   *
   * @param array $type_options
   *
   * @throws \Exception
   */
  protected function createType($label, $id, array $clone_fields, $type_options = []) {
    $this->drupalGet('admin/config/workflow/scheduled-update-type/add');
    // Revision options should not be displayed until entity type that supports it is selected.
    $this->assertNoText('The owner of the last revision.');
    $this->assertNoText('Create New Revisions');
    $edit = $type_options + [
      'label' => $label,
      'id' => $id,
      'update_entity_type' => 'node',
      'update_runner[id]' => 'default_independent',
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
      'id' => $id,
      'clone_field' => 'multiple-field',
      'update_entity_type' => 'node',
      'update_runner[id]' => 'default_independent',
      'update_runner[after_run]' => UpdateRunnerInterface::AFTER_DELETE,
      'update_runner[invalid_update_behavior]' => UpdateRunnerInterface::INVALID_DELETE,
      'update_runner[update_user]' => UpdateRunnerInterface::USER_UPDATE_RUNNER,
      'update_runner[create_revisions]' => UpdateRunnerInterface::REVISIONS_YES,
      'update_runner[bundles][article]' => 'article',
    ];
    $this->drupalPostForm(NULL,
      $edit,
      t('Save')
    );
    $this->assertUrl("admin/config/workflow/scheduled-update-type/$id/clone-fields");
    $this->assertText("Created the $label Scheduled Update Type.");
    $this->assertText("Select fields to add to these updates");
    $this->checkNodeProperties();
    // @todo test that node.body displays and is select field.

    $this->cloneFields($id, $clone_fields);

  }

  protected function setupContentTypes() {
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'article',
        'name' => 'Article'
      ));
      $this->drupalCreateContentType(array(
        'type' => 'page',
        'name' => 'Basic Page'
      ));
    }
  }

  /**
   * Check that a set of runner plugins are on form and no extras.
   *
   * @param array $expected_runners
   */
  protected function checkRunnersAvailable(array $expected_runners = []) {
    $all_runners = ['default_embedded', 'default_independent', 'latest_revision'];
    if (!$expected_runners) {
      $expected_runners = $all_runners;
    }
    $unexpected_runners = array_diff($all_runners, $expected_runners);
    $this->checkExpectedRadioOptions('update_runner[id]', $expected_runners, $unexpected_runners);
  }

  /**
   * Get Node Property Labels.
   *
   * @return array
   */
  protected function getNodePropertyLabels() {
    $property_labels = [
      'Language',
      'Title',
      'Authored by',
      'Publishing status',
      'Authored on',
      'Changed',
      'Promoted to front page',
      'Sticky at top of lists',
      'Revision timestamp',
      'Revision user ID',
      'Revision log message',
      'Default translation',
    ];
    return $property_labels;
  }

  /**
   * Runs Updates via the UI.
   *
   * @throws \Exception
   */
  protected function runUpdatesUI() {
    $this->drupalGet('admin/config/workflow/schedule-updates/run');
    $this->drupalPostForm(NULL, [], 'Run Updates');
  }

  /**
   * Checks that an scheduled update type can be edit via the form.
   *
   * @param string $type_id
   *   The type id.
   */
  protected function checkEditType($type_id) {
    $this->drupalGet("admin/config/workflow/scheduled-update-type/$type_id");
    // For now just test the saving without changes works.
    // See https://www.drupal.org/node/2674874
    $this->drupalPostForm(NULL, [], t('Save'));
  }

  /**
   * Grant permissions to a user.
   *
   * The permissions are actually added to the users role.
   * Relies on test users only having 1 non-locked role.
   *
   * @param array $permissions
   */
  protected function grantPermissionsToUser($permissions) {
    $roles = $this->adminUser->getRoles(TRUE);
    $this->assert('debug', "roles =" . implode(',', $roles));
    $role_id = array_pop($roles);
    $this->grantPermissions(Role::load($role_id), $permissions);
  }

  /**
   * Grant permissions to a user.
   *
   * The permissions are actually added to the users role.
   * Relies on test users only having 1 non-locked role.
   *
   * @param array $permissions
   */
  protected function revokePermissionsFromUser($permissions) {
    $roles = $this->adminUser->getRoles(TRUE);

    $role_id = array_pop($roles);
    foreach ($permissions as $permission) {
      Role::load($role_id)->revokePermission($permission);
    }
  }

}
