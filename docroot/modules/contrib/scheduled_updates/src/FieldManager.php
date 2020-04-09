<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\FieldManager.
 */


namespace Drupal\scheduled_updates;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;

/**
 * Field Manager for handling fields for Scheduled Updates.
 *
 */
class FieldManager implements FieldManagerInterface {

  use StringTranslationTrait;

  use FieldUtilsTrait;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * UpdateRunner constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, ConfigFactoryInterface $config_factory) {
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $config_factory;

  }

  /**
   * [@inheritdoc}
   */
  public function cloneField(ScheduledUpdateTypeInterface $scheduled_update_type, $field_name, $field_config_id = NULL, array $default_value = [], $hide = FALSE) {
    $entity_type = $scheduled_update_type->getUpdateEntityType();
    $definition = $this->getFieldStorageDefinition($entity_type, $field_name);
    if (!$definition) {
      return FALSE;
    }

    $new_field_name = $this->getNewFieldName($definition);
    $field_storage_values = [
      'field_name' => $new_field_name,
      'entity_type' => 'scheduled_update',
      'type' => $definition->getType(),
      'translatable' => $definition->isTranslatable(),
      'settings' => $definition->getSettings(),
      'cardinality' => $definition->getCardinality(),
     // 'module' => $definition->get @todo how to get module
    ];
    $field_values = [
      'field_name' => $new_field_name,
      'entity_type' => 'scheduled_update',
      'bundle' => $scheduled_update_type->id(),
      'label' => $definition->getLabel(),
      // Field translatability should be explicitly enabled by the users.
      'translatable' => FALSE,
    ];

    /** @var FieldConfig $field_config */
    if ($field_config_id && $field_config = FieldConfig::load($field_config_id)) {
      $field_values['settings'] = $field_config->getSettings();
      $field_values['label'] = $field_config->label();
    }

    // @todo Add Form display settings!

    FieldStorageConfig::create($field_storage_values)->save();
    /** @var FieldConfigBase $field */
    $field = FieldConfig::create($field_values);
    $field->save();
    if ($default_value) {
      $field->setDefaultValue($default_value);
      $field->save();
    }
    if (!$hide) {
      $this->createFormDisplay($scheduled_update_type, $field_config_id, $definition, $new_field_name);
    }

    return $field;
  }

  /**
   * Gets the first available field name for a give source field.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *
   * @return string
   * @internal param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface
   *           $scheduled_update_type
   *
   */
  protected function getNewFieldName(FieldStorageDefinitionInterface $definition) {
    $field_name = $definition->getName();
    if ($definition->isBaseField()) {
      $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $field_name;
    }
    return $this->createNonExistingFieldName($field_name, 'scheduled_update');
  }

  /**
   * {@inheritdoc}
   */
  public function fieldNameExists($field_name, $entity_type_id) {
    $definition = $this->getFieldStorageDefinition($entity_type_id, $field_name);
    return !empty($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFieldConfigsForField(FieldStorageDefinitionInterface $definition, $entity_type_id) {
    $map = $this->entityFieldManager->getFieldMap()[$entity_type_id];
    $definitions = [];
    $field_name = $definition->getName();
    if (isset($map[$field_name])) {
      $bundles = $map[$field_name]['bundles'];
      foreach ($bundles as $bundle) {
        $definitions[$bundle] = $this->getFieldDefinition($entity_type_id, $bundle, $field_name);
      }
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewReferenceField(array $new_field_settings, ScheduledUpdateTypeInterface $scheduled_update_type) {
    $entity_type = $scheduled_update_type->getUpdateEntityType();
    $field_name = $this->createNonExistingFieldName($new_field_settings['field_name'], $entity_type);
    $label = $new_field_settings['label'];
    if ($new_field_settings['cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $new_field_settings['cardinality_number'] = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
     }
    $field_storage_values = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference',
      'translatable' => FALSE,
      'settings' => ['target_type' => 'scheduled_update'],
      'cardinality' => $new_field_settings['cardinality_number'], // @todo Add config to form
      // 'module' => $definition->get @todo how to get module
    ];
    FieldStorageConfig::create($field_storage_values)->save();
    $bundles = array_filter($new_field_settings['bundles']);
    foreach ($bundles as $bundle) {
      $field_values = [
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $label,
        // Field translatability should be explicitly enabled by the users.
        'translatable' => FALSE,
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$scheduled_update_type->id()],
          ],
        ],
      ];
      $field = FieldConfig::create($field_values);
      $field->save();
      $this->addToDefaultFormDisplay($entity_type, $bundle, $label, $field_name);


    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateExistingReferenceFields(array $existing_field_settings, ScheduledUpdateTypeInterface $scheduled_update_type) {
    $field_id = $existing_field_settings['field_id'];
    $entity_type_id = $scheduled_update_type->getUpdateEntityType();
    /** @var FieldConfig $selected_config_field */
    $selected_config_field = FieldConfig::load($field_id);
    $field_storage = $selected_config_field->getFieldStorageDefinition();
    $config_fields = $this->getAllFieldConfigsForField($field_storage, $scheduled_update_type->getUpdateEntityType());
    $current_bundles = array_keys($config_fields);
    $needed_bundles = array_diff($existing_field_settings['bundles'], $current_bundles);
    /** @var FieldConfig $config_field */
    foreach ($config_fields as $config_field) {
      $settings = $config_field->getSetting('handler_settings');
      $settings['target_bundles'][] = $scheduled_update_type->id();
      $config_field->setSetting('handler_settings', $settings);
      $config_field->save();
    }
    if ($needed_bundles) {

      foreach ($needed_bundles as $needed_bundle) {
        $field_values = [
          'field_name' => $field_storage->getName(),
          'entity_type' => $entity_type_id,
          'bundle' => $needed_bundle,
          'label' => $selected_config_field->label(),
          // Field translatability should be explicitly enabled by the users.
          'translatable' => $selected_config_field->isTranslatable(),
          'settings' => [
            'handler_settings' => [
              'target_bundles' => [$scheduled_update_type->id()],
            ],
          ],
        ];
        $new_field = FieldConfig::create($field_values);
        $new_field->save();
        $this->addToDefaultFormDisplay($entity_type_id, $needed_bundle, $selected_config_field->label(), $field_storage->getName());
      }
    }
  }

  /**
   * Create an used field name adding the suffix number until an used one is found.
   *
   * @param $field_name
   *
   * @param $entity_type_id
   *
   * @return string
   */
  protected function createNonExistingFieldName($field_name, $entity_type_id) {
    $suffix = 0;
    $new_field_name = $field_name;
    while ($this->fieldNameExists($new_field_name, $entity_type_id)) {
      $suffix++;
      $new_field_name = $field_name . '_' . $suffix;
    }
    return $new_field_name;
  }

  /**
   * @param $entity_type
   * @param $bundle
   * @param $label
   * @param $field_name
   */
  protected function addToDefaultFormDisplay($entity_type, $bundle, $label, $field_name) {
    /** @var EntityFormDisplay $formDisplay */
    $formDisplay = EntityFormDisplay::load("$entity_type.$bundle.default");
    $form_options = [
      'type' => 'inline_entity_form_complex',
      'weight' => '11',
      'settings' => [
        'override_labels' => TRUE,
        'label_singular' => $label,
        'label_plural' => $label . 's',
        'allow_new' => TRUE,
        'match_operator' => 'CONTAINS',
        'allow_existing' => FALSE,
      ],
    ];
    $formDisplay->setComponent($field_name, $form_options);
    $formDisplay->save();
  }

  /**
   * {@inheritdoc}
   */
  function FieldManager() {
    return $this->entityFieldManager;
  }

  /**
   * Create a form display for a newly clone field.
   *
   * This function attempts to use same setting settings as the source field.
   *
   * @param \Drupal\scheduled_updates\ScheduledUpdateTypeInterface $scheduled_update_type
   * @param $field_name
   * @param $field_config_id
   * @param $entity_type
   * @param $definition
   *  Source field definition
   * @param $new_field_name
   */
  protected function createFormDisplay(ScheduledUpdateTypeInterface $scheduled_update_type, $field_config_id, FieldStorageDefinitionInterface $definition, $new_field_name) {
    $destination_bundle = $scheduled_update_type->id();
    $field_name = $definition->getName();
    $entity_type = $scheduled_update_type->getUpdateEntityType();
    /** @var EntityFormDisplay $destination_form_display */
    $destination_form_display = EntityFormDisplay::load("scheduled_update.$destination_bundle.default");
    if (empty($destination_form_display)) {
      $destination_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'scheduled_update',
        'bundle' => $destination_bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $display_options = [];
    if ($field_config_id) {
      $parts = explode('.', $field_config_id);
      $source_bundle = $parts[1];
      /** @var EntityFormDisplay $source_form_display */
      $source_form_display = EntityFormDisplay::load("$entity_type.$source_bundle.default");

      $display_options = $source_form_display->getComponent($field_name);
    }
    else {
      if ($definition instanceof BaseFieldDefinition) {
        $display_options = $definition->getDisplayOptions('form');
        if (empty($display_options)) {
          if ($definition->getType()) {
            // Provide default display for base boolean fields that don't have their own form display
            $display_options = [
              'type' => 'boolean_checkbox',
              'settings' => ['display_label' => TRUE],
            ];
          }
        }
      }
    }
    if (empty($display_options)) {
      $display_options = [];
    }
    if ($destination_form_display) {
      $destination_form_display->setComponent($new_field_name, $display_options);
      $destination_form_display->save();
    }
    else {
      // Alert user if display options could not be created.
      // @todo Create default display options even none on source.
      drupal_set_message(
        $this->t(
          'Form display options could not be created for @field they will have to be created manually.',
          ['@field' => $field_name]
        ),
        'warning');
    }
  }
}
