<?php

namespace Drupal\Tests\workbench_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests moderation state field.
 * @group workbench_moderation
 */
class ModerationStateFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workbench_moderation', 'node', 'views', 'options', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * {@inheritdoc}
   *
   * Covers moderation_state_install().
   */
  public function testModerationStateFieldIsCreated() {
    // There should be no updates as moderation_state_install() should have
    // applied the new field.
    $this->assertTrue(empty($this->container->get('entity.definition_update_manager')->needsUpdates()['node']));
    $this->assertTrue(!empty($this->container->get('entity_field.manager')->getFieldStorageDefinitions('node')['moderation_state']));
  }

}
