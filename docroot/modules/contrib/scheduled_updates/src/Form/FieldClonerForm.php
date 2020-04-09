<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Form\FieldClonerForm.
 */

namespace Drupal\scheduled_updates\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\scheduled_updates\ClassUtilsTrait;
use Drupal\scheduled_updates\FieldManagerInterface;
use Drupal\scheduled_updates\FieldUtilsTrait;
use Drupal\scheduled_updates\ScheduledUpdateTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FieldClonerForm.
 *
 * @package Drupal\scheduled_updates\Form
 */
class FieldClonerForm extends FormBase {

  use ClassUtilsTrait;

  use FieldUtilsTrait;
  /**
   * Drupal\Core\Entity\EntityFieldManager definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entity_field_manager;

  /**
   * @var ScheduledUpdateTypeInterface
   */
  protected $entity;

  /**
   * @var \Drupal\scheduled_updates\FieldManagerInterface
   */
  protected $fieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityFieldManager $entity_field_manager, FieldManagerInterface $fieldManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->entity_field_manager = $entity_field_manager;
    $this->fieldManager = $fieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('scheduled_updates.field_manager'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduled_updates_field_cloner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ScheduledUpdateTypeInterface $scheduled_update_type = NULL) {
    $this->entity = $scheduled_update_type;
    $form += $this->createFieldsElements($form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clone Fields'),
    ];
    return $form;
  }

  /**
   * Create field elements for all field on the entity type to update.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function createFieldsElements(FormStateInterface $form_state) {

    $base_options = [];
    /** @var FieldStorageDefinitionInterface[] $config_fields */
    $config_fields = [];
    $entity_type = $this->entity->getUpdateEntityType();
    $target_entity_label = $this->targetTypeLabel($this->entity);
    $target_bundle_label = $this->targetTypeBundleLabel($this->entity);
    $elements = [
      '#type' => 'container',
    ];

    $destination_fields = $this->getDestinationFields($entity_type);
    $map = $this->entity->getFieldMap();

    foreach ($destination_fields as $destination_field) {
      $field_name = $destination_field->getName();
      if (!in_array($field_name, $map)) {
        if ($destination_field->isBaseField()) {
          $base_options[$field_name] = $destination_field->getLabel();
        }
        else {
          $config_fields[] = $destination_field;
        }
      }

    }

    $elements['base_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Base Fields'),
      '#options' => $base_options,
      '#description' => $this->t(
        'These fields are available on all @label entities and may not be configurable.',
        ['@label' => $target_entity_label]
      ),
    ];

    $elements['config_fields'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Configurable Fields'),
    ];
    if ($this->targetSupportBundles($this->entity)) {
      $elements['config_fields']['#description'] = $this->t(
        'These fields have been added to different @bundle_label bundles of @entity_label. They may not be on all @entity_label entities.',
        [
          '@entity_label' => $target_entity_label,
          '@bundle_label' => $target_bundle_label,
        ]
      );
    }
    else {
      $elements['config_fields']['#description'] = $this->t(
        'These fields have been added to @entity_label entities.',
        [
          '@entity_label' => $target_entity_label,
        ]
      );
    }
    foreach ($config_fields as $config_field) {
      $instances = $this->fieldManager->getAllFieldConfigsForField($config_field, $entity_type);
      if ($instances) {
        $instance_options = ['' => $this->t('(Don\'t clone)')];
        foreach ($instances as $bundle => $instance) {
          $instance_options[$instance->id()] = $this->t(
            'As it is configured in @bundle as @label',
            ['@bundle' => $bundle, '@label' => $instance->getLabel()]
          );
        }
        $elements['config_fields'][$config_field->getName()] = [
          '#type' => 'select',
          '#title' => $config_field->getLabel(),
          '#options' => $instance_options,

        ];
      }


    }
    //$this->entity_field_manager->getFieldDefinitions();

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $base_fields = array_filter($form_state->getValue('base_fields'));
    $new_map = [];
    // Clone Base Fields
    foreach ($base_fields as $base_field) {
      /** @var \Drupal\field\Entity\FieldConfig $new_field */
      $new_field = $this->fieldManager->cloneField($this->entity, $base_field);
      $new_map[$new_field->getName()] = $base_field;
    }
    // Clone Configurable Fields
    if ($config_field_ids = $form_state->getValue('config_fields')) {
      foreach ($config_field_ids as $config_field_id) {
        /** @var FieldConfig $config_definition */
        if ($config_definition = FieldConfig::load($config_field_id)) {
          $field_name = $config_definition->getFieldStorageDefinition()
            ->getName();
          $new_field = $this->fieldManager->cloneField($this->entity, $field_name, $config_definition->id());
          $new_map[$new_field->getName()] = $field_name;
        }
      }
    }

    if ($new_map) {
      // Update Map
      $this->entity->addNewFieldMappings($new_map);
      $this->entity->save();
      drupal_set_message($this->t('The fields have been created and mapped.'));
      if ($this->currentUser()->hasPermission('administer scheduled_update form display')) {
        // Redirect to form display so user and adjust settings.
        $form_state->setRedirectUrl(Url::fromRoute("entity.entity_form_display.scheduled_update.default", array(
          $this->entity->getEntityTypeId() => $this->entity->id(),
        )));
      }
      else {
          drupal_set_message(
          $this->t('You do not have permission to administer fields on Scheduled Updates.'),
          'warning'
        );
      }

    }

  }

  function FieldManager() {
    return $this->entity_field_manager;
  }

  function getEntity() {
    return $this->entity;
  }


}
