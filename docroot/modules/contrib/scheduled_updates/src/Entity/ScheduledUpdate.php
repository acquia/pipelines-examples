<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Entity\ScheduledUpdate.
 */

namespace Drupal\scheduled_updates\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\scheduled_updates\ScheduledUpdateInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Scheduled update entity.
 *
 * @ingroup scheduled_updates
 *
 * @ContentEntityType(
 *   id = "scheduled_update",
 *   label = @Translation("Scheduled update"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\scheduled_updates\ScheduledUpdateListBuilder",
 *     "views_data" = "Drupal\scheduled_updates\Entity\ScheduledUpdateViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\scheduled_updates\Entity\Form\ScheduledUpdateForm",
 *       "add" = "Drupal\scheduled_updates\Entity\Form\ScheduledUpdateForm",
 *       "edit" = "Drupal\scheduled_updates\Entity\Form\ScheduledUpdateForm",
 *       "delete" = "Drupal\scheduled_updates\Entity\Form\ScheduledUpdateDeleteForm",
 *     },
 *     "access" = "Drupal\scheduled_updates\ScheduledUpdateAccessControlHandler",
 *   },
 *   base_table = "scheduled_update",
 *   admin_permission = "administer ScheduledUpdate entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "update_timestamp",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/scheduled_update/{scheduled_update}",
 *     "edit-form" = "/admin/scheduled_update/{scheduled_update}/edit",
 *     "delete-form" = "/admin/scheduled_update/{scheduled_update}/delete"
 *   },
 *   bundle_entity_type = "scheduled_update_type",
 *   field_ui_base_route = "entity.scheduled_update_type.edit_form"
 * )
 */
class ScheduledUpdate extends ContentEntityBase implements ScheduledUpdateInterface {
  use EntityChangedTrait;
  use StringTranslationTrait;



  /**
   * @return mixed
   */
  public function getUpdateEntityIds() {
    $ids = [];
    $field_values = $this->entity_ids->getValue();
    foreach ($field_values as $field_value) {
      $ids[] = $field_value['target_id'];
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdateEntityIds(array $update_entity_ids) {
    $this->entity_ids = $update_entity_ids;
  }
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**˙
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Scheduled update entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Scheduled update type/bundle.'))
      ->setSetting('target_type', 'scheduled_update_type')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Scheduled update entity.'))
      ->setReadOnly(TRUE);

    $status_options = [
      ScheduledUpdateInterface::STATUS_UNRUN => 'Un-run',
      ScheduledUpdateInterface::STATUS_INQUEUE => 'In Queue',
      ScheduledUpdateInterface::STATUS_REQUEUED => 'Re-queued',
      ScheduledUpdateInterface::STATUS_SUCCESSFUL => 'Successful',
      ScheduledUpdateInterface::STATUS_UNSUCESSFUL => 'Un-successful',
      ScheduledUpdateInterface::STATUS_INACTIVE => 'Inactive',
    ];
    // @todo Change this field to list_integer so that Views can easily filter by label.
    $fields['status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the update.'))
      ->setDefaultValue(ScheduledUpdateInterface::STATUS_UNRUN)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -100,
      ))
      ->setSetting('allowed_values', $status_options);


    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user ID of author of the Scheduled update entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\scheduled_updates\Entity\ScheduledUpdate::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);


    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Scheduled update entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['update_timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Update Date/time'))
      ->setDescription(t('The time that the update will happen.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_ids'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entities to Update'))
      ->setDescription(t('The entities that will be updated.'))
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var ScheduledUpdateType $update_type */
    if ($update_type = ScheduledUpdateType::load($bundle)) {
      $fields['entity_ids'] = clone $base_field_definitions['entity_ids'];

      /** @var BaseFieldDefinition $definition */
      $definition =& $fields['entity_ids'];

      if ($update_type->isIndependentType()) {


        // @todo Update other setting per bundle: cardinality, required, default display etc.
        // @todo Add reference field settings on Type edit page
        $definition->setSetting('target_type', $update_type->getUpdateEntityType());
        $definition->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'weight' => -10,
        ));
        $runner_settings = $update_type->getUpdateRunnerSettings();
        if (isset($runner_settings['bundles'])) {
          $bundles = array_filter($runner_settings['bundles']);
          $definition->setSetting('handler_settings', ['target_bundles' => $bundles]);
        }
      }
      else {
        $definition->setDisplayConfigurable('form', FALSE);
        $definition->setDisplayConfigurable('view', FALSE);
      }

      return $fields;
    }
    return array();
  }
  public function label() {
    if (!$this->get('update_timestamp')->isEmpty()) {
      /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
      $formatter = \Drupal::service('date.formatter');
      return $formatter->format($this->get('update_timestamp')->getString());
    }
    else {
      return $this->t('No update time specified');
    }
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage); // TODO: Change the autogenerated stub
  }

  /**
   * Determine whether the update is in an archived state.
   *
   * @return bool
   */
  public function isArchived() {
    return $this->status->value == ScheduledUpdateInterface::STATUS_SUCCESSFUL
      || $this->status->value === ScheduledUpdateInterface::STATUS_UNSUCESSFUL;
  }


}
