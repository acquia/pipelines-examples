<?php

namespace Drupal\Tests\workbench_moderation\Kernel;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Entity\ModerationStateTransition;

/**
 * Ensures that workbench moderation schema is correct.
 *
 * @group workbench_moderation
 */
class WorkbenchModerationSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workbench_moderation', 'node', 'user', 'block_content', 'system'];

  /**
   * Tests workbench moderation default schema.
   */
  public function testWorkbenchModerationDefaultConfig() {
    $this->installConfig(['workbench_moderation']);
    $typed_config = \Drupal::service('config.typed');
    $moderation_states = ModerationState::loadMultiple();
    foreach ($moderation_states as $moderation_state) {
      $this->assertConfigSchema($typed_config, $moderation_state->getEntityType()->getConfigPrefix(). '.' . $moderation_state->id(), $moderation_state->toArray());
    }
    $moderation_state_transitions = ModerationStateTransition::loadMultiple();
    foreach ($moderation_state_transitions as $moderation_state_transition) {
      $this->assertConfigSchema($typed_config, $moderation_state_transition->getEntityType()->getConfigPrefix(). '.' . $moderation_state_transition->id(), $moderation_state_transition->toArray());
    }

  }

  /**
   * Tests workbench moderation third party schema for node types.
   */
  public function testWorkbenchModerationNodeTypeConfig() {
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['workbench_moderation']);
    $typed_config = \Drupal::service('config.typed');
    $moderation_states = ModerationState::loadMultiple();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', array_keys($moderation_states));
    $node_type->setThirdPartySetting('workbench_moderation', 'default_moderation_state', '');
    $node_type->save();
    $this->assertConfigSchema($typed_config, $node_type->getEntityType()->getConfigPrefix(). '.' . $node_type->id(), $node_type->toArray());
  }

  /**
   * Tests workbench moderation third party schema for block content types.
   */
  public function testWorkbenchModerationBlockContentTypeConfig() {
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');
    $this->installConfig(['workbench_moderation']);
    $typed_config = \Drupal::service('config.typed');
    $moderation_states = ModerationState::loadMultiple();
    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => TRUE,
    ]);
    $block_content_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $block_content_type->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', array_keys($moderation_states));
    $block_content_type->setThirdPartySetting('workbench_moderation', 'default_moderation_state', '');
    $block_content_type->save();
    $this->assertConfigSchema($typed_config, $block_content_type->getEntityType()->getConfigPrefix(). '.' . $block_content_type->id(), $block_content_type->toArray());
  }

}
