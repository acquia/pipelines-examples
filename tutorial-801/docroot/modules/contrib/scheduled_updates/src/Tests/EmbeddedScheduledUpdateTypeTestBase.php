<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Tests\EmbeddedScheduledUpdateTypeTestBase.
 */


namespace Drupal\scheduled_updates\Tests;

/**
 * Base test class for embedded update types.
 *
 * There are differences in how the types are create but after that they have
 * the same testing needs.
 * @see checkAfterTypeCreated()
 *
 * This also contain utility functions dealing with Inline Entity Form.
 */
abstract class EmbeddedScheduledUpdateTypeTestBase extends ScheduledUpdatesTestBase {
  /**
   * Make sure Referenced types do not have a direct add form.
   *
   * @param $label
   * @param $type_id
   *
   */
  protected function confirmNoAddForm($label, $type_id) {
    $this->loginWithPermissions(["create $type_id scheduled updates"]);
    $this->drupalGet('admin/content/scheduled-update/add');
    $this->assertNoText($label, 'Refereneced type label does not appear on update add page.');
    $this->assertNoText('Update Date/time', 'Update time field is not available on add page.');
    $this->checkAccessDenied('admin/content/scheduled-update/add/' . $type_id);
    $this->loginLastUser();

  }
  /**
   * Make sure that reference field was created and put on target entity type.
   *
   * @param $entity_type
   * @param $bundle
   * @param $reference_field_label
   * @param $reference_field_name
   */
  protected function checkReferenceCreated($entity_type, $bundle, $reference_field_label, $reference_field_name) {
    $this->loginWithPermissions([
      'administer node fields',
      'administer content types',
    ]);
    $this->drupalGet("admin/structure/types/manage/$bundle/fields");
    $this->assertText($reference_field_label);
    $this->assertText($reference_field_name);
    $this->loginLastUser();
  }


  /**
   * Make sure that reference field was created and put on target entity type.
   *
   * @param $entity_type
   * @param $bundle
   * @param $reference_field_label
   * @param $reference_field_name
   */
  protected function checkReferenceOnEntityType($entity_type, $bundle, $reference_field_label, $reference_field_name) {
    $this->loginWithPermissions(["create $bundle content"]);
    $this->drupalGet("node/add/$bundle");
    $this->assertText($reference_field_label);
    // @todo Check html for field
    $this->loginLastUser();
    // $field_id = 'edit-' . str_replace('_', '-', $reference_field_name);
    //$this->assertFieldbyId($field_id);
  }

  /**
   * @param $edit
   * @param $add_url
   * @param $reference_field_label
   * @param $reference_field_name
   *
   * @return array
   * @throws \Exception
   */
  protected function checkNewFieldRequired(array &$edit, $add_url, $reference_field_label, $reference_field_name) {
    // Save first without new field information.
    // This is only enforce by javascript states
    // @see \Drupal\scheduled_updates\Form\ScheduledUpdateTypeBaseForm::validateForm

    // Remove label explicitly.
    $edit['reference_settings[new_field][label]'] = '';
    $edit['reference_settings[new_field][field_name]'] = '';
    $this->drupalPostForm(NULL,
      $edit,
      t('Save')
    );
    $this->assertText('Please provide a name for the new field.', "NEW Field name require for $reference_field_label.");
    $this->assertText('Please provide a label for the new field.', "NEW Field label require for $reference_field_label.");
    $this->assertUrl($add_url);

    $edit['reference_settings[new_field][label]'] = $reference_field_label;
    $edit['reference_settings[new_field][field_name]'] = $reference_field_name;

    return $edit;
  }

  /**
   * Get the URL for adding an entity.
   *
   * Hard-coded for node style path now
   *
   * @param $entity_type
   * @param $bundle
   *
   * @return string
   */
  protected function getEntityAddURL($entity_type, $bundle) {
    return "$entity_type/add/$bundle";
  }

  /**
   * Gets IEF button name.
   *
   * Copied from IEF module.
   *
   * @param array $xpath
   *   Xpath of the button.
   *
   * @return string
   *   The name of the button.
   */
  protected function getButtonName($xpath) {
    $retval = '';
    /** @var \SimpleXMLElement[] $elements */
    if ($elements = $this->xpath($xpath)) {
      foreach ($elements[0]->attributes() as $name => $value) {
        if ($name == 'name') {
          $retval = $value;
          break;
        }
      }
    }
    return $retval;
  }

  /**
   * Submit a IEF Form with Ajax.
   *
   * @param $label
   * @param $drupal_selector
   * @param array $edit
   *
   * @throws \Exception
   */
  protected function submitIEFForm($label, $drupal_selector, $edit = []) {
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName("//input[@type=\"submit\" and @value=\"$label\" and @data-drupal-selector=\"$drupal_selector\"]"));
  }

  /**
   * Checking adding and running updates for title.
   *
   * Hardcoded for node 'title' property now.
   *
   * @param $bundle
   * @param $reference_field_name
   * @param $reference_field_label
   *
   * @internal param $entity_type
   * @internal param $update_fields
   */
  protected function checkRunningTitleUpdates($bundle, $reference_field_name, $reference_field_label) {
    $update_node = $this->createNodeWithUpdate('Title to be updated', '-1 year', $bundle, $reference_field_name, $reference_field_label);
    $no_update_node = $this->createNodeWithUpdate('Title NOT to be updated', '+1 year', $bundle, $reference_field_name, $reference_field_label);

    $this->runUpdatesUI();
    $this->drupalGet("node/" . $update_node->id());
    $this->assertText('Title to be updated:updated', 'Update title appears on past update');
    $this->drupalGet("node/" . $no_update_node->id());
    $this->assertText("Title NOT to be updated", 'Original node title appears on future update');
    $this->assertNoText("Title NOT to be updated:updated", 'Update title does not appear on future update');
  }

  /**
   * Checking adding and running updates for title.
   *
   * Hardcoded for node 'title' property now.
   *
   * @param $bundle
   * @param $reference_field_name
   * @param $reference_field_label
   *
   * @internal param $entity_type
   * @internal param $update_fields
   */
  protected function checkRunningPromoteUpdates($bundle, $reference_field_name, $reference_field_label) {
    $update_node = $this->createNodeWithUpdate('Upate Node', '-1 year', $bundle, $reference_field_name, $reference_field_label, TRUE);
    $no_update_node = $this->createNodeWithUpdate('No update node', '+1 year', $bundle, $reference_field_name, $reference_field_label, TRUE);

    $this->runUpdatesUI();
    $this->checkEntityValue('node', $update_node, 'promote', 1);
    $this->checkEntityValue('node', $no_update_node, 'promote', 0);
  }

  /**
   * @param $title
   * @param $date_offset
   * @param $bundle
   * @param $reference_field_name
   * @param $reference_field_label
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Exception
   */
  protected function createNodeWithUpdate($title, $date_offset, $bundle, $reference_field_name, $reference_field_label, $field_hidden = FALSE) {
    $id_field_name = str_replace('_', '-', $reference_field_name);
    $entity_add_url = $this->getEntityAddURL('node', $bundle);
    $this->drupalGet($entity_add_url);
    $ief_button = "Add new $reference_field_label";
    $this->assertText($ief_button);
    // Open IEF form
    $this->submitIEFForm($ief_button, "edit-$id_field_name-actions-ief-add");
    // Check opened form.
    $this->assertText('Update Date/time');

    // Submit IEF form
    // Create ief_test_complex node.
    $edit = [
      "{$reference_field_name}[form][inline_entity_form][update_timestamp][0][value][date]" => $this->getRelativeDate($date_offset),
      "{$reference_field_name}[form][inline_entity_form][update_timestamp][0][value][time]" => '01:00:00',
    ];
    if ($field_hidden) {
      // Hard-coded for now. @todo Create parameter to this function.
      //promote_reference[form][inline_entity_form][field_promote][value]
      $ief_field_name = "{$reference_field_name}[form][inline_entity_form][field_promote][value]";
      $this->assertNoFieldByName($ief_field_name, NULL, "$reference_field_name - hides update field");
    }
    else {
      // Hard-coded for now. @todo Create parameter to this function.
      $ief_field_name = "{$reference_field_name}[form][inline_entity_form][field_title][0][value]";
      $this->assertFieldByName($ief_field_name);
      $edit[$ief_field_name] = "$title:updated";
    }
    $this->submitIEFForm("Create $reference_field_label", "edit-$id_field_name-form-inline-entity-form-actions-ief-add-save", $edit);
    $this->assertResponse(200, 'Saving IEF Update was successful.');

    // Create ief_test_complex node.
    $edit = ['title[0][value]' => $title];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $this->assertResponse(200, 'Saving parent entity was successful.');

    $node = $this->drupalGetNodeByTitle($title);
    return $node;
  }

  /**
   * @param $label
   * @param $type_id
   * @param $reference_field_label
   * @param $reference_field_name
   */
  protected function checkAfterTypeCreated($label, $type_id, $reference_field_label, $reference_field_name, $clone_field) {
    $permissions = [
      "create $type_id scheduled updates",
      'administer scheduled updates',
    ];
    // Check both permissions tha will allow the user to create updates.
    foreach ($permissions as $permission) {
      // Give permission to create the current update type.
      $this->grantPermissionsToUser([$permission]);
      $this->confirmNoAddForm($label, $type_id);
      $this->checkReferenceCreated('node', 'page', $reference_field_label, $reference_field_name);
      $this->checkReferenceOnEntityType('node', 'page', $reference_field_label, $reference_field_name);
      switch ($clone_field) {
        case 'title':
          $this->checkRunningTitleUpdates('page', $reference_field_name, $reference_field_label);
          break;
        case 'promote':
          $this->checkRunningPromoteUpdates('page', $reference_field_name, $reference_field_label);
          break;
      }
      $this->revokePermissionsFromUser([$permission]);
    }
  }

}
