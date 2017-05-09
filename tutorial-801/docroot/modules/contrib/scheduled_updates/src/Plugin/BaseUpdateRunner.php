<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Plugin\BaseUpdateRunner.
 */


namespace Drupal\scheduled_updates\Plugin;


use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\scheduled_updates\ClassUtilsTrait;
use Drupal\scheduled_updates\Entity\ScheduledUpdate;
use Drupal\scheduled_updates\Entity\ScheduledUpdateType;
use Drupal\scheduled_updates\ScheduledUpdateInterface;
use Drupal\scheduled_updates\ScheduledUpdateTypeInterface;
use Drupal\scheduled_updates\UpdateUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseUpdateRunner extends PluginBase implements UpdateRunnerInterface {

  use ClassUtilsTrait;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface  */
  protected $entityTypeManager;
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface  */
  protected $fieldManager;

  /**
   * @var \Drupal\scheduled_updates\UpdateUtils
   */
  protected $updateUtils;

  /**
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /** @var  \Drupal\scheduled_updates\entity\ScheduledUpdateType */
  protected $scheduled_update_type;

  /**
   * The entity reference field ids target connected update types.
   *
   * @var
   */
  protected $field_ids;


  /**
   * Queue items that will be released after updates in queue are run.
   *
   * The items were not valid updates.
   *
   * @var array
   */
  protected $items_to_release;

  /**
   * If the runner is currently switched to a different user.
   * @var boolean
   */
  protected $isUserSwitched;


  /**
   * Get After Run behavior configuration.
   *
   * @return mixed
   */
  public function getAfterRun() {
    return $this->configuration['after_run'];
  }

  /**
   * BaseUpdateRunner constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\scheduled_updates\UpdateUtils $updateUtils
   * @param \Drupal\Core\Session\AccountSwitcherInterface $accountSwitcher
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $fieldManager, EntityTypeManagerInterface $entityTypeManager, UpdateUtils $updateUtils, AccountSwitcherInterface $accountSwitcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldManager = $fieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->updateUtils = $updateUtils;
    $this->accountSwitcher = $accountSwitcher;
    if (!empty($configuration['updater_type'])) {
      $this->scheduled_update_type = ScheduledUpdateType::load($configuration['updater_type']);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('scheduled_updates.update_utils'),
      $container->get('account_switcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addUpdatesToQueue() {
    $updates = $this->getAllUpdates();
    if ($updates) {
      $queue = $this->getQueue();

      foreach ($updates as $update) {
        $queue->createItem($update);
        /** @var ScheduledUpdate $update */
        $update = ScheduledUpdate::load($update['update_id']);
        $update->status = ScheduledUpdateInterface::STATUS_INQUEUE;
        $update->save();
      }
    }
  }

  /**
   * Get all entity ids for entities that reference updates that are ready to run.
   *
   * This default function will only get default entity revisions.
   *
   * @return array
   *  - Values = Entity ids
   *  - keys = revision ids for revisionable entites, entity ids for non-revisionable entities
   *    This is because of the return from \Drupal\Core\Entity\Query\QueryInterface::execute
   */
  protected function getEntityIdsReferencingReadyUpdates() {
    $entity_ids = [];
    if ($field_ids = $this->getReferencingFieldIds()) {
      $entity_storage = $this->entityTypeManager->getStorage($this->updateEntityType());
      foreach ($field_ids as $field_id) {

        $query = $entity_storage->getQuery('AND');
        $this->addActiveUpdateConditions($query, "$field_id.entity.");
        $entity_ids += $query->execute();
      }
    }
    return $entity_ids;
  }





  /**
   * {@inheritdoc}
   */
  public function runUpdatesInQueue($time_end) {
    $queue = $this->getQueue();

    $invalid_updates_cnt = 0;
    $valid_updates_cnt = 0;
    while ($time_end > time()) {
      if ($update_info = $queue->claimItem(10)) {
        /** @var ScheduledUpdate $update */
        if ($update = ScheduledUpdate::load($update_info->data['update_id'])) {
          // @todo Validate $update timestamp code have been changed since adding to queue
          //   if update is in queue should it edit access always be false?
          if (isset($update_info->data['entity_ids'])) {
            $entity_ids = $update_info->data['entity_ids'];
          }
          else {
            $entity_ids = $update->getUpdateEntityIds();
          }
          if ($this->runUpdate($update, $entity_ids, $update_info)) {
            $valid_updates_cnt++;
          }
          else {
            $invalid_updates_cnt++;
          }

        }
        else {
          // Could not load update that is in queue.
          $queue->deleteItem($update_info);
        }
      }
      else {
        // No more items can be claimed.
        break;
      }

    }
    $this->displayMessage($valid_updates_cnt, $invalid_updates_cnt);
    $this->releaseClaimedItems();

  }

  /**
   * Run an individual update from the queue.
   *
   * The update may involve multiple entities.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   *
   * @param array $entity_ids
   *  Ids of entities that should be updated
   * @param $queue_item
   *
   * @return bool
   */
  protected function runUpdate(ScheduledUpdateInterface $update, $entity_ids, $queue_item) {
    /** @var ContentEntityInterface[] $entities_to_update */
    $entities_to_update = $this->loadEntitiesToUpdate($entity_ids);

    $invalid_entity_ids = [];
    foreach (array_keys($entities_to_update) as $entity_id) {
      $entity_to_update = $entities_to_update[$entity_id];
      $this->prepareEntityForUpdate($update, $queue_item, $entity_to_update);
      $this->switchUser($update, $entity_to_update);
      $violations = $entity_to_update->validate();
      if (count($violations)) {
        $invalid_entity_ids[] = $entity_id;
      }
      else {
        // Validation was successful.
        $entity_to_update->save();
      }
      $this->switchUserBack();
    }

    // @todo Should an update only be consider successfull if all entites were update correctly.
    // @todo Should all entities should be rolled back if 1 can't be updated? Add an option?
    $successful = empty($invalid_entity_ids);
    if (!$successful) {
      // At least 1 entity could not be updated.
      if ($this->getInvalidUpdateBehavior() == UpdateRunnerInterface::INVALID_REQUEUE) {
        // We can't release the item now or it will be claimed again.
        $update->status = ScheduledUpdateInterface::STATUS_REQUEUED;
        if ($this->scheduled_update_type->isEmbeddedType()) {
          $update->setUpdateEntityIds($invalid_entity_ids);
        }
        $update->save();
        $this->addItemToRelease($queue_item);
      }
      elseif ($this->getInvalidUpdateBehavior() == UpdateRunnerInterface::INVALID_ARCHIVE) {
        // @todo Should the successful entities be removed
        $update->status = ScheduledUpdateInterface::STATUS_UNSUCESSFUL;
        $update->save();
        $this->getQueue()->deleteItem($queue_item);
      }
      else {
        $update->delete();
        $this->getQueue()->deleteItem($queue_item);
      }
    }
    else {
      // There were no invalid entity updates
      if ($this->getAfterRun() == UpdateRunnerInterface::AFTER_DELETE) {
        $update->delete();
      }
      else {
        $update->status = ScheduledUpdateInterface::STATUS_SUCCESSFUL;
        $update->save();
      }
      $this->getQueue()->deleteItem($queue_item);
    }
    return $successful;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencingFieldIds() {
    if (!isset($this->field_ids)) {
      $this->field_ids = [];
      $entity_reference_fields = $this->fieldManager->getFieldMapByFieldType('entity_reference');
      $update_entity_type = $this->updateEntityType();
      if (empty($entity_reference_fields[$update_entity_type])) {
        return $this->field_ids;
      }
      $entity_reference_fields = $entity_reference_fields[$update_entity_type];
      foreach ($entity_reference_fields as $field_id => $entity_reference_field) {
        foreach ($entity_reference_field['bundles'] as $bundle) {

          $field = $this->fieldManager->getFieldDefinitions($update_entity_type, $bundle)[$field_id];
          if ($field instanceof FieldConfig) {

            if ($field->getSetting('target_type') == 'scheduled_update'
              && !empty($field->getSetting('handler_settings')['target_bundles'])
              && in_array($this->configuration['updater_type'], $field->getSetting('handler_settings')['target_bundles'])
            ) {
              $this->field_ids[] = $field_id;
            }
          }
        }
      }
    }

    return $this->field_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityType() {
    return $this->scheduled_update_type->getUpdateEntityType();
  }

  /**
   * Transfer field values from update to entity to be updated.
   *
   * Because different fields may be on different bundles
   * not all fields will be transferred to all entities.
   *
   * @param $update
   * @param $entity_to_update
   */
  protected function transferFieldValues(ScheduledUpdateInterface $update, ContentEntityInterface $entity_to_update) {
    $field_map = $this->scheduled_update_type->getFieldMap();
    foreach ($field_map as $from_field => $to_field) {
      if ($to_field) {
        if ($entity_to_update->hasField($to_field) && $update->hasField($from_field)) {
          $new_value = $update->get($from_field)->getValue();
          // @todo if $new_value is empty. Check to see if the field is required on the target?
          //  If it is require don't update value because this cause a fatal at least on base fields.

          if (isset($new_value)) {
            $entity_to_update->set($to_field, $new_value);
          }
        }
      }
    }
  }

  /**
   * Display message about updates.
   *
   * @param $update_count
   * @param $invalid_count
   */
  protected function displayMessage($update_count, $invalid_count) {
    $msg = $this->t('Updater @title complete. Results:', ['@title' => $this->scheduled_update_type->label()]);
    if ($update_count) {
      $msg .= ' ' . $this->t('@count update(s) were performed.', ['@count' => $update_count]);
    }
    else {
      $msg .= ' ' . $this->t('No updates were performed.');
    }
    if ($invalid_count) {
      $msg .= ' ' . $this->t('@count updates were invalid', ['@count' => $invalid_count]);
    }
    drupal_set_message($msg);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueue() {
    return \Drupal::queue('scheduled_updates:' . $this->configuration['updater_type'],TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $form_state->get('update_runner');

    /** @var ScheduledUpdateType $type */
    $type = $this->getUpdateType($form_state);

    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->getPluginId(),
    ];

    $form['runner_advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Runner Options'),
      '#collasped' => TRUE,
      '#weight' => 100,
      //'#tree' => FALSE,
    ];

    $form['runner_advanced']['after_run'] = [
      '#type' => 'select',
      '#title' => $this->t('After update behavior'),
      '#description' => $this->t('What should happen after updates are run?'),
      '#required' => TRUE,
      '#default_value' => !empty($settings['after_run'])? $settings['after_run']: UpdateRunnerInterface::AFTER_DELETE,
      '#options' => [
        UpdateRunnerInterface::AFTER_DELETE => $this->t('Delete Updates'),
        UpdateRunnerInterface::AFTER_ARCHIVE => $this->t('Archive Updates'),
      ],
      '#tree' => TRUE,
      '#weight' => '0',
    ];
    $form['runner_advanced']['invalid_update_behavior'] = [
      '#type' => 'select',
      '#title' => $this->t('Invalid update behavior'),
      '#description' => $this->t('What should happen when an update is invalid?'),
      '#required' => TRUE,
      '#default_value' => !empty($settings['invalid_update_behavior'])? $settings['invalid_update_behavior']: UpdateRunnerInterface::INVALID_DELETE,
      '#options' => [
        UpdateRunnerInterface::INVALID_REQUEUE => $this->t('Leave update in queue'),
        UpdateRunnerInterface::INVALID_DELETE => $this->t('Delete update'),
        UpdateRunnerInterface::INVALID_ARCHIVE => $this->t('Archive update'),
       ],
      '#weight' => '20',
    ];

    if ($type && $this->updateUtils->supportsRevisionUpdates($type)) {
      $revision_options = [];
      if ($this->updateUtils->supportsRevisionBundleDefault($type)) {
        $revision_options = [
          UpdateRunnerInterface::REVISIONS_BUNDLE_DEFAULT => $this->t('Use bundle default.'),
        ];
      }
      $revision_options += [
        UpdateRunnerInterface::REVISIONS_YES => $this->t('Always Create New Revisions'),
        UpdateRunnerInterface::REVISIONS_NO => $this->t('Never Create New Revisions'),
      ];
      $form['runner_advanced']['create_revisions'] = [
        '#type' => 'select',
        '#title' => $this->t('Create New Revisions'),
        '#description' => $this->t('Should updates create new revisions of entities? Not all entity types support revisions.'),
        '#required' => TRUE,
        '#default_value' => !empty($settings['create_revisions'])? $settings['create_revisions']: UpdateRunnerInterface::REVISIONS_BUNDLE_DEFAULT,
        '#options' => $revision_options,
        '#weight' => '40',
      ];
    }
    $update_user_options = [
      UpdateRunnerInterface::USER_UPDATE_RUNNER => $this->t('The user who using running the updates. User #1 in cron.'),
      UpdateRunnerInterface::USER_UPDATE_OWNER => $this->t('The owner of the update.'),
    ];

    if ($type && $this->updateUtils->supportsOwner($type)) {
      $update_user_options[UpdateRunnerInterface::USER_OWNER] = $this->t('The owner of the entity to be updated');
    }
    if ($type && $this->updateUtils->supportsRevisionOwner($type)) {
      $update_user_options[UpdateRunnerInterface::USER_REVISION_OWNER] = $this->t('The owner of the last revision.');
    }
    $form['runner_advanced']['update_user'] = [
      '#type' => 'select',
      '#title' => $this->t('Run update as:'),
      '#description' => $this->t('Which user should the updates be run as?'),
      '#required' => TRUE,
      '#default_value' => !empty($settings['update_user'])? $settings['update_user']: UpdateRunnerInterface::USER_UPDATE_RUNNER,
      '#options' => $update_user_options,
      '#weight' => '60',
    ];

    // Remove fieldset from parents.
    foreach (Element::children($form['runner_advanced']) as $key) {
      $form['runner_advanced'][$key]['#parents'] = ['update_runner',$key];
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvalidUpdateBehavior() {
    return $this->configuration['invalid_update_behavior'];
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * @param $queue_item
   */
  protected function addItemToRelease($queue_item) {
    $this->items_to_release[] = $queue_item;
  }

  protected function releaseClaimedItems() {
    $queue = $this->getQueue();
    if ($this->items_to_release) {
      foreach ($this->items_to_release as $item) {
        $queue->releaseItem($item);
      }
    }

  }

  /**
   * Add conditions to a query to select updates to run.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   * @param string $condition_prefix
   *  String to attach to the beginning of all conditions if the base table is not updates.
   */
  protected function addActiveUpdateConditions(QueryInterface $query, $condition_prefix = '') {
    $query->condition($condition_prefix. 'update_timestamp', REQUEST_TIME, '<=');
    $query->condition($condition_prefix. 'type', $this->scheduled_update_type->id());
    $query->condition(
      $condition_prefix. 'status',
      [
        ScheduledUpdateInterface::STATUS_UNRUN,
        // @todo How to handle requeued items.
        //ScheduledUpdateInterface::STATUS_REQUEUED,
      ],
      'IN'
    );
  }

  /**
   * Remove update from reference field value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   * @param $field_id
   */
  protected function removeUpdate(ContentEntityInterface $entity, ScheduledUpdateInterface $update, $field_id) {
    $current_update_ids = $this->getEntityReferenceTargetIds($entity,$field_id);
    $new_update_ids = array_diff($current_update_ids, [$update->id()]);
    $entity->get($field_id)->setValue($new_update_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceTargetIds(ContentEntityInterface $entity, $field_name, $sort = FALSE) {
    $target_ids = [];
    if ($entity->hasField($field_name)) {
      $field_values = $entity->get($field_name)->getValue();

      foreach ($field_values as $field_value) {
        $target_ids[] = $field_value['target_id'];
      }
    }
    if ($sort) {
      asort($target_ids);
    }
    return $target_ids;
  }

  /**
   * Get updates that are ready to be run for this Runner.
   *
   * @param array $update_ids
   *  If given updates will be restrict to this array.
   *
   * @return array
   *  Update ids.
   */
  protected  function getReadyUpdateIds($update_ids = []) {
    $entity_storage = $this->entityTypeManager->getStorage('scheduled_update');
    $query = $entity_storage->getQuery('AND');
    $this->addActiveUpdateConditions($query);
    if ($update_ids) {
      $query->condition('id', $update_ids, 'IN');
    }
    return $query->execute();
  }

  /**
   * Prepare an entity to be updated.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   * @param $queue_item
   * @param ContentEntityInterface $entity_to_update
   */
  protected function prepareEntityForUpdate(ScheduledUpdateInterface $update, $queue_item, ContentEntityInterface $entity_to_update) {
    $this->transferFieldValues($update, $entity_to_update);
    if (!empty($queue_item->data['field_id'])) {
      $this->removeUpdate($entity_to_update, $update, $queue_item->data['field_id']);
    }
    $this->setEntityRevision($entity_to_update, $update);
  }

  /**
   * Set a entity to use a new revision is applicable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_to_update
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   */
  protected function setEntityRevision(ContentEntityInterface $entity_to_update, ScheduledUpdateInterface $update) {
    if ($this->updateUtils->isRevisionableUpdate($update)) {
      $new_revision = FALSE;
      $create_revisions = $this->configuration['create_revisions'];
      if ($create_revisions == UpdateRunnerInterface::REVISIONS_BUNDLE_DEFAULT) {
        $new_revision = $this->updateUtils->getRevisionDefault($entity_to_update);
      }
      elseif ($create_revisions == UpdateRunnerInterface::REVISIONS_YES) {
        $new_revision = TRUE;
      }
      $entity_to_update->setNewRevision($new_revision);
      if ($new_revision) {
        $this->updateUtils->setRevisionCreationTime($entity_to_update);
      }
    }
  }

  /**
   * Switch to another user to run an update if necessary.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateInterface $update
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_to_update
   */
  protected function switchUser(ScheduledUpdateInterface $update, ContentEntityInterface $entity_to_update) {
    $update_user = $this->configuration['update_user'];
    $switch_to_user = NULL;
    switch ($update_user) {
      case $this::USER_UPDATE_RUNNER:
        // Running the update as the update runner means there is no need to switch
        return;
      case $this::USER_OWNER:
        $switch_to_user = $this->getEntityOwner($entity_to_update);
        break;
      case $this::USER_REVISION_OWNER:
        $switch_to_user = $this->getRevisionOwner($entity_to_update);
        break;
      case $this::USER_UPDATE_OWNER:
        $switch_to_user = $update->getOwner();
        break;
    }
    if ($switch_to_user) {
      // @todo Throw an error because we should have a user.
      $this->accountSwitcher->switchTo($switch_to_user);
      $this->isUserSwitched = TRUE;
    }

  }

  /**
   * If the user has been switch to run an update switch the user back.
   */
  protected function switchUserBack() {
    if ($this->isUserSwitched) {
      $this->accountSwitcher->switchBack();
      $this->isUserSwitched = FALSE;
    }
  }

  /**
   * Load multi entities to update.
   *
   * @param $entity_ids
   *  Keys of array should be revision id for revisionable entities
   *  Keys for non-revisionable entities will be entity keys.
   *
   * @return ContentEntityInterface[]
   */
  protected function loadEntitiesToUpdate($entity_ids) {
    return $this->entityTypeManager->getStorage($this->updateEntityType())->loadMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!empty($this->pluginDefinition['description'])) {
      return $this->pluginDefinition['description'];
    }
    return '';
  }


  /**
   * Get all schedule updates for this types that should be added to queue.
   *
   * @return ScheduledUpdate[]
   */
  abstract protected function getAllUpdates();

  /**
   * Get Scheduled Update Type from the Form State.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return ScheduledUpdateTypeInterface|null
   */
  protected function getUpdateType(FormStateInterface $form_state) {
    return $form_state->get('scheduled_update_type');
  }

}
