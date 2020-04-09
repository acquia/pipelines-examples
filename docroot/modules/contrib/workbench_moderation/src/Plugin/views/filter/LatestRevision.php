<?php

namespace Drupal\workbench_moderation\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Annotation\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter to show only the latest revision of an entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("latest_revision")
 */
class LatestRevision extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinHandler;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new LatestRevision.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_handler
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ViewsHandlerManager $join_handler, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->joinHandler = $join_handler;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.views.join'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() { }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function canExpose() { return FALSE; }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // The table doesn't exist until a moderated node has been saved at least
    // once. Just in case, disable this filter until then. Note that this means
    // the view will still show all revisions, not just latest, but this is
    // sufficiently edge-case-y that it's probably not worth the time to
    // handle more robustly.
    if (!$this->connection->schema()->tableExists('workbench_revision_tracker')) {
      return;
    }

    $table = $this->ensureMyTable();

    /** @var Sql $query */
    $query = $this->query;

    $definition = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $definition->getKeys();

    $definition = [
      'table' => 'workbench_revision_tracker',
      'type' => 'INNER',
      'field' => 'entity_id',
      'left_table' => $table,
      'left_field' => $keys['id'],
      'extra' => [
        ['left_field' => $keys['langcode'], 'field' => 'langcode'],
        ['left_field' => $keys['revision'], 'field' => 'revision_id'],
        ['field' => 'entity_type', 'value' => $this->getEntityType()],
      ],
    ];

    $join = $this->joinHandler->createInstance('standard', $definition);

    $query->ensureTable('workbench_revision_tracker', $this->relationship, $join);
  }
}
