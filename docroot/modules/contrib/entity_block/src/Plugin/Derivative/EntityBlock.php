<?php

namespace Drupal\entity_block\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for all Entity Block block displays.
 *
 * @see \Drupal\entity_block\Plugin\block\block\EntityBlock
 */
class EntityBlock implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a ViewsBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entityTypeManager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entityDefinition) {
      if ($entityDefinition->hasViewBuilderClass()) {
        $delta = $entityDefinition->id();
        $this->derivatives[$delta] = [
          'category' => 'Entity Block',
          'admin_label' => $entityDefinition->getLabel(),
          'config_dependencies' => [
            'config' => [
              // TODO: Add proper config dependencies.
            ],
          ],
        ];
        $this->derivatives[$delta] += $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
