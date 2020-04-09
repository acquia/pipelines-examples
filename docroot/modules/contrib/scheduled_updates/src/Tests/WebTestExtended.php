<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Tests\WebTestExtended.
 */


namespace Drupal\scheduled_updates\Tests;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * WebTestBase plus project agnostic helper functions.
 */
abstract class WebTestExtended extends WebTestBase{

  /**
   * Store last user to easily login back in.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $last_user;

  /**
   * Create user and login with given permissions.
   *
   * @param array $permissions
   *
   * @return \Drupal\user\Entity\User|false
   * @throws \Exception
   */
  protected function loginWithPermissions(array $permissions) {
    if ($user = $this->createUser($permissions)) {
      $this->drupalLogin($user);
      return $user;
    }
    throw new \Exception('Could not create user.');
  }

  /**
   * Overridden to add easy switch back functionality.
   *
   * {@inheritdoc}
   */
  protected function drupalLogin(AccountInterface $account) {
    $this->last_user = $this->loggedInUser;
    parent::drupalLogin($account);
  }

  /**
   * Login previous user.
   *
   * If no previous user this logic problem with the test.
   */
  protected function loginLastUser() {
    if ($this->last_user) {
      $this->drupalLogin($this->last_user);
    }
    else {
      throw new \Exception('No last user. Testing logic exception.');
    }
  }

  /**
   * Check an entity value after reload.
   *
   * @param $entity_type_id
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $field
   * @param $value
   */
  protected function checkEntityValue($entity_type_id, ContentEntityInterface $entity, $field, $value) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $storage->resetCache([$entity->id()]);
    $updated_entity = $storage->load($entity->id());
    $this->assertEqual($updated_entity->get($field)->value, 1, $entity->label() . " $field = $value");
  }

  /**
   * Utility Function to get a date relative from current.
   *
   * @param $modify
   *
   * @return string
   */
  protected function getRelativeDate($modify, $format = 'Y-m-d') {
    $date = new \DateTime();
    $date->modify($modify);
    return $date->format($format);
  }

  /**
   * Utility function to check that a select has only the expected options.
   *
   * @param $select_id
   * @param $expected_options
   * @param array $unexpected_options
   */
  protected function checkExpectedOptions($select_id, $expected_options, $unexpected_options = []) {
    foreach ($expected_options as $expected_option) {
      $this->assertOption($select_id, $expected_option);
    }
    foreach ($unexpected_options as $unexpected_option) {
      $this->assertNoOption($select_id, $unexpected_option);
    }
  }

  /**
   * Utility function to check that a radio group has only the expected options.
   *
   * @param $name
   * @param $expected_options
   * @param array $unexpected_options
   */
  protected function checkExpectedRadioOptions($name, $expected_options, $unexpected_options = []) {
    foreach ($expected_options as $expected_option) {
      $this->assertFieldByName($name,$expected_option);

    }
    foreach ($unexpected_options as $unexpected_option) {
      $this->assertNoFieldByName($name, $unexpected_option);
    }
  }

  /**
   * Utility Function around drupalGet to avoid call if not needed.
   *
   * @param $path
   */
  protected function gotoURLIfNot($path) {
    if ($path != $this->getUrl()) {
      $this->drupalGet($path);
    }
  }

  /**
   * Utility function to check that current user does not access to a given path.
   *
   * @param null $path
   */
  protected function checkAccessDenied($path = NULL) {
    if ($path) {
      $this->drupalGet($path);
    }
    $this->assertText('Access denied', 'Accessed denied on path: ' . $path);
  }

}
