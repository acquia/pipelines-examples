<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Plugin\UpdateRunnerManager.
 */


namespace Drupal\scheduled_updates\Plugin;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class UpdateRunnerManager extends DefaultPluginManager{

  /**
   * Constructs a new ImageEffectManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/UpdateRunner', $namespaces, $module_handler, 'Drupal\scheduled_updates\Plugin\UpdateRunnerInterface', 'Drupal\scheduled_updates\Annotation\UpdateRunner');
    $this->alterInfo('scheduled_updates_runners_info');
    $this->setCacheBackend($cache_backend, 'scheduled_updates_runners');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    if (isset($definitions['default'])) {
      // Always put default first.
      $definitions = ['default' => $definitions['default']] + $definitions;
    }
    return $definitions;
  }

}
