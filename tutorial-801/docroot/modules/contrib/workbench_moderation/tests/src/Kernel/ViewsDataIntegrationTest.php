<?php

namespace Drupal\Tests\workbench_moderation\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the views integration of workbench_moderation.
 *
 * @group workbench_moderation
 */
class ViewsDataTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workbench_moderation_test_views',
    'node',
    'workbench_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installConfig('workbench_moderation_test_views');
  }

  public function testViewsData() {
    $node_type = NodeType::create([
      'type' => 'page',
    ]);
    $node_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $node_type->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test title first revision',
      'moderation_state' => 'published',
    ]);
    $node->save();

    $revision = clone $node;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->title->value = 'Test title second revision';
    $revision->moderation_state->target_id = 'draft';
    $revision->save();

    $view = Views::getView('test_workbench_moderation_latest_revision');
    $view->execute();

    // Ensure that the workbench_revision_tracker contains the right latest
    // revision ID.
    // Also ensure that the relationship back to the revision table contains the
    // right latest revision.
    $expected_result = [
      [
        'nid' => $node->id(),
        'revision_id' => $revision->getRevisionId(),
        'title' => $revision->label(),
        'moderation_state_revision' => 'published',
        'moderation_state' => 'published',
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, ['nid' => 'nid', 'workbench_revision_tracker_revision_id' => 'revision_id', 'moderation_state_revision' => 'moderation_state_revision', 'moderation_state' => 'moderation_state']);
  }

}
