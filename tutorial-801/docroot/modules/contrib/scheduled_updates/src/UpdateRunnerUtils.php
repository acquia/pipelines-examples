<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\UpdateRunnerUtils.
 */


namespace Drupal\scheduled_updates;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\scheduled_updates\Entity\ScheduledUpdateType;
use Drupal\scheduled_updates\Plugin\EntityMonitorUpdateRunnerInterface;
use Drupal\scheduled_updates\Plugin\UpdateRunnerInterface;
use Drupal\scheduled_updates\Plugin\UpdateRunnerManager;

/**
 * A service that runs all available updaters
 */
class UpdateRunnerUtils {

  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface  */
  protected $entityFieldManager;

  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface  */
  protected $entityTypeBundleInfo;

  /** @var \Drupal\scheduled_updates\Plugin\UpdateRunnerManager  */
  protected $runnerManager;

  /**
   * @var \Drupal\scheduled_updates\UpdateUtils
   */
  protected $updateUtils;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * UpdateRunner constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\scheduled_updates\Plugin\UpdateRunnerManager $runnerManager
   * @param \Drupal\scheduled_updates\UpdateUtils $updateUtils
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    UpdateRunnerManager $runnerManager,
    UpdateUtils $updateUtils,
    ConfigFactoryInterface $configFactory
    ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->runnerManager = $runnerManager;
    $this->updateUtils = $updateUtils;
    $this->config = $configFactory->get('scheduled_updates.settings');
  }


  /**
   * Get update runner for scheduled update types.
   *
   * @param array $update_types
   *  Update ids to load runners for. If empty return all.
   *
   * @return \Drupal\scheduled_updates\Plugin\UpdateRunnerInterface[]
   */
  protected function getUpdateTypeRunners(array $update_types = []) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('scheduled_update');
    /** @var UpdateRunnerInterface[] $runners */
    $runners = [];
    foreach ($bundles as $bundle => $bundle_info) {
      if (empty($update_types) || in_array($bundle, $update_types)) {
        /** @var ScheduledUpdateType $updater */
        $updater = ScheduledUpdateType::load($bundle);

        if ($runner = $this->getUpdateRunnerInstance($updater)) {
          $runners[$bundle] = $runner;
        }
        else {
          // @todo User logger for missing plugins
          /* drupal_set_message(t('Missing plugin in @plugin_id in update type @type',
            [
              '@plugin_id' => $runner_settings['id'],
              '@type' => $updater->label(),
            ])); */
        }
      }
    }
    return $runners;

  }

  /**
   * Get the Runner Plugin Instance for a Type.
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @return null|object
   */
  public function getUpdateRunnerInstance(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    $runner_settings = $scheduledUpdateType->getUpdateRunnerSettings();
    $runner_settings['updater_type'] = $scheduledUpdateType->id();

    if ($this->runnerManager->hasDefinition($runner_settings['id'])) {
      return $this->runnerManager->createInstance($runner_settings['id'], $runner_settings);
    }
    return NULL;
  }

  protected function getTimeout() {
    return (int)$this->config->get('timeout')? $this->config->get('timeout') : 15;
  }

  /**
   * Run run updates for all types.
   *
   * @param array $update_types
   *  Ids of updates to run
   */
  public function   runAllUpdates(array $update_types= []) {
    $time_end = time() + $this->getTimeout();
    $runners = $this->getUpdateTypeRunners($update_types);
    foreach ($runners as $runner) {
      $runner->addUpdatesToQueue();
      $runner->runUpdatesInQueue($time_end);
      if ($time_end < time()) {
        break;
      }
    }

  }

  public function invokeEntityUpdate(ContentEntityInterface $entity) {
    $runners = $this->getUpdateTypeRunners();
    foreach ($runners as $runner) {
      if ($runner instanceof EntityMonitorUpdateRunnerInterface
        && $runner->updateEntityType() == $entity->getEntityTypeId()) {
        // Check to see if update reference fields have changed.
        if ($this->entityUpdatesChanged($entity, $runner)) {
          $runner->onEntityUpdate($entity);
          break;
        }
      }
    }

  }

  public function isEmbeddedUpdater(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    return $this->updateSupportsTypes($scheduledUpdateType, ['embedded']);

  }

  public function isIndependentUpdater(ScheduledUpdateTypeInterface $scheduledUpdateType) {
    return $this->updateSupportsTypes($scheduledUpdateType, ['independent']);
  }

  /**
   * Check to see if Updates attached to an entity has changed.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \Drupal\scheduled_updates\Plugin\EntityMonitorUpdateRunnerInterface $runner
   *
   * @return bool
   */
  protected function entityUpdatesChanged(ContentEntityInterface $entity, EntityMonitorUpdateRunnerInterface $runner) {
    // We can't use $entity->original because it is not always the last revision.
    /** @var ContentEntityInterface $previous */
    if ($entity->getEntityType()->isRevisionable()) {
      $previous = $this->updateUtils->getPreviousRevision($entity);
    }
    else {
      $previous = isset($entity->original)? $entity->original: NULL;
    }

    if ($previous) {
      $field_ids = $runner->getReferencingFieldIds();
      foreach ($field_ids as $field_id) {
        $previous_update_ids = $runner->getEntityReferenceTargetIds($previous, $field_id, TRUE);
        $updated_update_ids = $runner->getEntityReferenceTargetIds($entity, $field_id, TRUE);
        if ($previous_update_ids != $updated_update_ids) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduledUpdateType
   *
   * @param $types
   *
   * @return bool
   */
  public function updateSupportsTypes(ScheduledUpdateTypeInterface $scheduledUpdateType, $types) {
    $plugin_id = $scheduledUpdateType->getUpdateRunnerSettings()['id'];
    if ($this->runnerManager->hasDefinition($plugin_id)) {
      $definition = $this->runnerManager->getDefinition($plugin_id);
      $unsupported_types = array_diff($types, $definition['update_types']);
      return empty($unsupported_types);
    }
    return FALSE;
  }

}
