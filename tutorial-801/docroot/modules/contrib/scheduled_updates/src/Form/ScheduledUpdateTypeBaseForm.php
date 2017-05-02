<?php
/**
 * @file
 * Contains \Drupal\scheduled_updates\Form\ScheduledUpdateTypeBaseForm.
 */


namespace Drupal\scheduled_updates\Form;


use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\scheduled_updates\ClassUtilsTrait;
use Drupal\scheduled_updates\Entity\ScheduledUpdateType;
use Drupal\scheduled_updates\FieldManagerInterface;
use Drupal\scheduled_updates\FieldUtilsTrait;
use Drupal\scheduled_updates\Plugin\UpdateRunnerInterface;
use Drupal\scheduled_updates\Plugin\UpdateRunnerManager;
use Drupal\scheduled_updates\UpdateUtilsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScheduledUpdateTypeBaseForm extends EntityForm{

  use FieldUtilsTrait;

  use ClassUtilsTrait;

  /** @var  ScheduledUpdateType */
  protected $entity;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\scheduled_updates\Plugin\UpdateRunnerManager
   */
  protected $runnerManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\scheduled_updates\FieldManagerInterface
   */
  protected $fieldManager;

  /**
   * @var \Drupal\scheduled_updates\UpdateUtilsInterface
   */
  protected $updateUtils;


  /**
   * Constructs a ScheduledUpdateTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\scheduled_updates\Plugin\UpdateRunnerManager $runnerManager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\scheduled_updates\FieldManagerInterface $fieldManager
   * @param \Drupal\scheduled_updates\UpdateUtilsInterface $updateUtils
   *
   * @internal param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, UpdateRunnerManager $runnerManager, ModuleHandlerInterface $moduleHandler, FieldManagerInterface $fieldManager, UpdateUtilsInterface $updateUtils) {
    $this->entityFieldManager = $entityFieldManager;
    $this->runnerManager = $runnerManager;
    $this->moduleHandler = $moduleHandler;
    $this->fieldManager = $fieldManager;
    $this->updateUtils = $updateUtils;

  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.scheduled_updates.update_runner'),
      $container->get('module_handler'),
      $container->get('scheduled_updates.field_manager'),
      $container->get('scheduled_updates.update_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function FieldManager() {
    return $this->entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['type_dependent_elements'] = [];

    // @todo Should the runner configuration form even be displayed before entity type is selected?
    $form['type_dependent_elements']['update_runner'] = $this->createRunnerElements($form_state);
    $form['type_dependent_elements']['update_runner']['#weight'] = 100;

    $form['type_dependent_elements']['update_runner']['id'] += $this->typeDependentAjax();

    if ($this->entity->isNew()) {
      $form['type_dependent_elements'] += $this->createCloneFieldSelect($form, $form_state);
      $form['type_dependent_elements']['reference_settings'] = $this->createNewFieldsElements($form, $form_state);
    }

    $form['type_dependent_elements'] += [
      '#type' => 'container',
      '#prefix' => '<div id="type-dependent-set" >',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Ajax Form call back for Update Runner Fieldset.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return
   */
  public function updateRunnerSettings(array $form, FormStateInterface $form_state) {
    $form_state->setValidationEnforced(FALSE);
    return $form['type_dependent_elements']['update_runner'];
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'field_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value, $element, FormStateInterface $form_state) {
    // Don't validate the case when an existing field has been selected.
    if ($form_state->getValue('existing_storage_name')) {
      return FALSE;
    }

    // Add the field prefix.
    $field_name = $this->configFactory->get('field_ui.settings')
        ->get('field_prefix') . $value;
    return $this->fieldManager->fieldNameExists($field_name, $this->entity->getUpdateEntityType());
  }

  /**
   * Ajax Form call back for Create Reference Fieldset.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return
   */
  public function updateTypeDependentSet(array $form, FormStateInterface $form_state) {
    // $form_state->setRebuild();
    $form_state->setValidationEnforced(FALSE);
    return $form['type_dependent_elements'];
  }

  /**
   * Create form elements for runner selection and options.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function createRunnerElements(FormStateInterface $form_state) {
    $runner_settings = $form_state->getValue('update_runner');
    $update_runner = $this->createRunnerInstance($runner_settings, $form_state);
    $elements = $update_runner->buildConfigurationForm([], $form_state);

    $runner_options = [];
    $runner_definitions = $this->getSupportedRunnerDefinitions();
    foreach ($runner_definitions as $definition) {
      /** @var UpdateRunnerInterface $runner_instance */
      $runner_instance = $this->runnerManager->createInstance($definition['id']);
      $runner_options[$definition['id']] = $definition['label']
        . ' - ' . $runner_instance->getDescription();
    }
    $elements['id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Update Runner'),
      '#options' => $runner_options,
      '#default_value' => $runner_settings['id'],
      '#limit_validation_errors' => array(),
      '#weight' => -30,
    ];
    $elements += [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Update Runner settings'),
    ];
    return $elements;
  }

  /**
   * Create options for create a new entity reference field.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function createNewFieldsElements(array &$form, FormStateInterface $form_state) {


    $entity_type = $this->getCurrentEntityType($form_state);

    $elements = [];


    if ($entity_type && $this->runnerSupportsEmbedded($this->entity->getUpdateRunnerSettings())) {
      $elements = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => 'Update Reference Options',
        '#prefix' => '<div id="create-reference-fieldset">',
        '#suffix' => '</div>',
      ];


      $elements['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Place field on bundles'),
        '#description' => $this->t('Choose which bundles to place this new field on.'),
        '#options' => $this->updateUtils->bundleOptions($entity_type),
        '#required' => TRUE,
      ];
      if ($target_bundle = $form_state->get('target_bundle')) {
        $elements['bundles']['#default_value'] = [$target_bundle];
      }


      if ($this->moduleHandler->moduleExists('inline_entity_form')) {
        // Only works with Inline Entity Form for now
        $existing_fields = $this->existingReferenceFields();

        if ($existing_fields) {
          $elements['reference_field_options'] = [
            '#type' => 'select',
            '#title' => $this->t('Reference Field Options'),
            '#options' => [
              'new' => $this->t('Create new reference fields to enter updates.'),
              'reuse' => $this->t('Re-use an existing reference field to enter updates.')
            ],
            '#required' => TRUE,
          ];
        }
        else {
          $elements['reference_field_options'] = [
            '#type' => 'value',
            '#value' => 'new',
          ];
        }


        $new_field_visible['#states'] = array(
          'visible' => array(
            ':input[name="reference_settings[reference_field_options]"]' => array('value' => 'new'),
          ),
          'required' => array(
            ':input[name="reference_settings[reference_field_options]"]' => array('value' => 'new'),
          ),
        );

        // Option #1 Create a New Field
        $elements['new_field'] = [
            '#type' => 'container',
            '#title' => $this->t('New Field'),
          ] + $new_field_visible;


        $elements['new_field']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#size' => 15,
          ] + $new_field_visible;

        $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
        $elements['new_field']['field_name'] = [
            '#type' => 'machine_name',
            // This field should stay LTR even for RTL languages.
            '#field_prefix' => '<span dir="ltr">' . $field_prefix,
            '#field_suffix' => '</span>&lrm;',
            '#size' => 15,
            '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
            // Calculate characters depending on the length of the field prefix
            // setting. Maximum length is 32.
            '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
            '#machine_name' => array(
              'source' => [
                'type_dependent_elements',
                'reference_settings',
                'new_field',
                'label'
              ],
              'exists' => array($this, 'fieldNameExists'),
            ),
            '#required' => FALSE,
          ] + $new_field_visible;

        $elements['new_field']['cardinality_container'] = array(
            // Reset #parents so the additional container does not appear.
            '#type' => 'fieldset',
            '#title' => $this->t('Update Limit'),
            '#description' => $this->t('How many updates updates should able to be added at one time?'),
            '#attributes' => array(
              'class' => array(
                'container-inline',
                'fieldgroup',
                'form-composite'
              )
            ),
          ) + $new_field_visible;
        $elements['new_field']['cardinality_container']['cardinality'] = array(
          '#type' => 'select',
          '#title' => $this->t('Allowed number of values'),
          '#title_display' => 'invisible',
          '#options' => array(
            'number' => $this->t('Limited'),
            FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => $this->t('Unlimited'),
          ),
          '#default_value' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
        );
        $elements['new_field']['cardinality_container']['cardinality_number'] = array(
          '#type' => 'number',
          '#default_value' => 1,
          '#min' => 1,
          '#title' => $this->t('Limit'),
          '#title_display' => 'invisible',
          '#size' => 2,
          '#states' => array(
            'visible' => array(
              ':input[name="reference_settings[new_field][cardinality_container][cardinality]"]' => array('value' => 'number'),
            ),
            'disabled' => array(
              ':input[name="reference_settings[new_field][cardinality_container][cardinality]"]' => array('value' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED),
            ),
          ),
        );
        if ($existing_fields) {
          $existing_field_visible['#states'] = array(
            'visible' => array(
              ':input[name="reference_settings[reference_field_options]"]' => array('value' => 'reuse'),
            ),
            'required' => array(
              ':input[name="reference_settings[reference_field_options]"]' => array('value' => 'reuse'),
            ),
          );

          $existing_options = [];
          foreach ($existing_fields as $existing_field) {
            // @todo Get bundle labels.
            $bundles = array_keys($existing_field['bundles']);
            $field_info = array_shift($existing_field['bundles']);
            $existing_options[$field_info['field_id']] = $field_info['label']
              . ': ' . $this->t('Used on') . ' ' . implode(', ', $bundles);

          }
          $elements['existing_field'] = [
              '#type' => 'radios',
              '#title' => $this->t('Existing Fields'),
              '#options' => $existing_options,
            ] + $existing_field_visible;
        }
      }
      else {

        $markup = '<p>' . $this->t('It is recommended that you use the <a href="https://www.drupal.org/project/inline_entity_form" >Inline Entity Form</a> module when creating updates directly on the entities to be updated.') . '</p>';
        $markup .= '<p>' . $this->t('Only proceed if you have an alternative method of creating new update entities on entities to be updated.');
        $elements['notice'] = [
          '#type' => 'markup',
          '#markup' => $markup,
        ];
      }
    }

    return $elements;
  }

  /**
   * Setup entity reference field for this update type on add.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function setUpFieldReferences(FormStateInterface $form_state) {
    $reference_settings = $form_state->getValue('reference_settings');
    $bundles = array_filter($reference_settings['bundles']);
    $field_use = $reference_settings['reference_field_options'];
    if ($field_use == 'new') {
      $new_field_settings = $reference_settings['new_field'];
      $new_field_settings += $reference_settings['new_field']['cardinality_container'];
      unset($new_field_settings['cardinality_container']);
      $new_field_settings['bundles'] = $bundles;
      $this->fieldManager->createNewReferenceField($new_field_settings, $this->entity);
    }
    elseif ($field_use == 'reuse') {
      $existing_field_settings['field_id'] = $reference_settings['existing_field'];
      $existing_field_settings['bundles'] = $bundles;
      $this->fieldManager->updateExistingReferenceFields($existing_field_settings, $this->entity);

    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($reference_settings = $form_state->getValue('reference_settings')) {
      $field_use = $reference_settings['reference_field_options'];
      if ($field_use == 'new') {
        $new_field_settings = $reference_settings['new_field'];
        if (empty($new_field_settings['field_name'])) {
          $form_state->setError($form['type_dependent_elements']['reference_settings']['new_field']['field_name'], $this->t('Please provide a name for the new field.'));
        }
        if (empty($new_field_settings['label'])) {
          $form_state->setError($form['type_dependent_elements']['reference_settings']['new_field']['label'], $this->t('Please provide a label for the new field.'));
        }

      }
    }

  }


  /**
   * Get existing entity reference field on target entity type that reference scheduled updates.
   *
   * @return array
   */
  protected function existingReferenceFields() {
    $entity_type = $this->entity->getUpdateEntityType();
    $fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    if (!isset($fields[$entity_type])) {
      return [];
    }
    $fields = $fields[$entity_type];
    $ref_fields = [];
    foreach ($fields as $field_name => $field_info) {
      if ($definition = FieldStorageConfig::loadByName($entity_type, $field_name)) {
        $update_type = $definition->getSetting('target_type');
        if ($update_type == 'scheduled_update') {
          $ref_fields[$field_name]['field_name'] = $field_name;
          $bundle_fields = [];
          foreach ($field_info['bundles'] as $bundle) {
            $field_config = FieldConfig::loadByName($entity_type, $bundle, $field_name);
            $bundle_fields[$bundle] = [
              'field_id' => $field_config->id(),
              'label' => $field_config->label(),
              'bundle' => $bundle,
            ];
          }
          $ref_fields[$field_name]['bundles'] = $bundle_fields;

        }
      }
    }
    return $ref_fields;
  }

  protected function runnerSupportsEmbedded($settings) {
    if ($this->runnerManager->hasDefinition($settings['id'])) {
      $definition = $this->runnerManager->getDefinition($settings['id']);
      return in_array('embedded', $definition['update_types']);
    }
    return FALSE;
  }

  /**
   * Create an instance of the Runner plugin to be used with this Update Type.
   *
   * @param $runner_settings
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\scheduled_updates\Plugin\UpdateRunnerInterface
   */
  protected function createRunnerInstance(&$runner_settings, FormStateInterface $form_state) {
    if (empty($runner_settings)) {
      $runner_settings = $this->entity->getUpdateRunnerSettings();
    }
    if (!$this->runnerManager->hasDefinition($runner_settings['id'])) {
      // Settings is using plugin which no longer exists.
      $runner_settings = [
        'id' => 'default_embedded'
      ];
    }

    /** @var UpdateRunnerInterface $update_runner */
    $update_runner = $this->runnerManager->createInstance($runner_settings['id'], $runner_settings);

    $form_state->set('update_runner', $runner_settings);
    $form_state->set('scheduled_update_type', $this->entity);
    return $update_runner;
  }

  /**
   * Create an option to select one field to clone on type add form.
   *
   * Probably most common situation is only 1 field per update so this would
   * skip 'clone fields' screen. The admin could always go back to that screen
   * to add more fields.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function createCloneFieldSelect(array $form, FormStateInterface $form_state) {
    $elements = [];
    if ($entity_type = $this->getCurrentEntityType($form_state)) {
      if ($bundle = $form_state->get('target_bundle')) {
        $options = $this->getBundleDestinationOptions($entity_type, $bundle);
      }
      else {
        $options = $this->getEntityDestinationOptions($entity_type);
      }
      $elements['clone_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Update Field'),
        '#options' => $options,
        '#description' => $this->t('Select the field you would like to update.'),
        '#required' => TRUE,
      ] + $this->typeDependentAjax();

      //$form_state->

      if ($field_selected = $form_state->getValue('clone_field')) {
        $elements['default_value'] = $this->createDefaultValueElements($field_selected, $form_state);
      }
    }


    return $elements;
  }

  /**
   * Get the current entity type to be updated for update type.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   *
   */
  protected function getCurrentEntityType(FormStateInterface $form_state) {
    $entity_type = $this->entity->getUpdateEntityType();
    if (!$entity_type) {
      $entity_type = $form_state->get('target_entity_type_id');
    }
    return $entity_type;
  }

  /**
   * @return array
   */
  protected function typeDependentAjax() {
    $ajax = [
      // Maybe don't need this anymore.
      //'#limit_validation_errors' => array(),
      '#ajax' => array(
        'wrapper' => 'type-dependent-set',
        'callback' => '::updateTypeDependentSet',
        'method' => 'replace',
      )
    ];
    return $ajax;
  }

  /**
   * Save the entity on submit.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return int
   */
  public function doSave(array $form, FormStateInterface $form_state) {
    $definition = $this->runnerManager->getDefinition($this->entity->getUpdateRunnerSettings()['id']);
    $this->entity->setUpdateTypesSupported($definition['update_types']);
    $status = $this->entity->save();
    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Created the %label Scheduled Update Type.', [
        '%label' => $this->entity->label(),
      ]));
      if (in_array('embedded',$this->entity->getUpdateTypesSupported())) {
        if ($form_state->getValue('reference_settings')) {
          $this->setUpFieldReferences($form_state);
        }
      }
    }
    return $status;

  }

  /**
   * Get definitions for Runner Plugins that should be supported.
   *
   * @return array|\mixed[]|null
   */
  protected function getSupportedRunnerDefinitions() {
    return $this->runnerManager->getDefinitions();
  }

  /**
   * Create the default value elements for a field.
   *
   *
   * @param $field_selected
   *
   * @return array
   */
  protected function createDefaultValueElements($field_selected, FormStateInterface $form_state) {
    $elements = [];
    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) array(
      'entity_type' => $this->getCurrentEntityType($form_state),
      'bundle' => $this->getDefaultBundle($field_selected, $form_state),
      'entity_id' => NULL
    );
    $form['#entity'] = _field_create_entity_from_ids($ids);
    /** @var FieldItemListInterface $items */
    $items = $form['#entity']->get($field_selected);
    $definition = $items->getFieldDefinition();
    if ($this->isDefaultCompatible($definition)) {
      $item = $items->first() ?: $items->appendItem();
      if ($widget_override = $this->getWidgetOverride($definition)) {
        $form_state->set('default_value_widget', $widget_override);
      }
      // Add handling for default value.

      if ($elements = $items->defaultValuesForm($form, $form_state)) {
        $elements = array_merge($elements , array(
          '#type' => 'details',
          '#title' => $this->t('Default value and Date Only Updates'),
          '#open' => TRUE,
          '#tree' => TRUE,
          '#description' => $this->t('The default value for this field, used when creating an update.'),
        ));
        $elements['#title'] = $this->t('Default value and Date Only Updates');
        $elements['_no_form_display'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide this field for an <strong>date only</strong> update.'),
          '#description' => $this->t('Hiding fields with a default value is very useful for creating updates where the user only has to enter an update date.'
            . ' ' . 'For example creating a "Publish Date" update type where user simply has to pick a date they would like the content published on.'
          ),
        ];
      }
    }

    return $elements;
  }

  /**
   * Determine if we should try to make a default value widget.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *
   * @return bool
   */
  private function isDefaultCompatible(FieldDefinitionInterface $definition) {
    $class = $definition->getClass();
    $type = $definition->getType();
    $compatible_types = [
      'boolean',
      'list_float',
      'list_integer',
      'list_string',
    ];
    if (in_array($type, $compatible_types)) {
      return TRUE;
    }
    if ('entity_reference' == $type) {
      // We should only store a default value in the special case
      // where the target is a config entity.
      // Otherwise we would have config that pointed to content.
      // The site-builder could still do this in manage fields for the Update type
      // but should not en-courage this practice.
      $target_type = $definition->getFieldStorageDefinition()->getSetting('target_type');
      $definition = $this->entityTypeManager->getDefinition($target_type);
      if ($this->definitionClassImplementsInterface($definition, ['Drupal\Core\Config\Entity\ConfigEntityInterface'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determine the default that should be used to create default value elements.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return string
   */
  protected function getDefaultBundle($field_selected, FormStateInterface $form_state) {
    $all_fields = $this->entityFieldManager->getFieldMap();
    $entity_fields = $all_fields[$this->entity->getUpdateEntityType()];
    $bundle = array_shift(array_keys($entity_fields[$field_selected]['bundles']));
    return $bundle;
  }

  /**
   * Clone a single field from the settings on type add form.
   *
   * This creates a default value for the field if chosen.
   *
   * @param $entity_type
   * @param $bundle
   * @param $clone_field
   */
  protected function cloneSingleField($entity_type,$clone_field, FormStateInterface $form_state, $bundle = NULL) {
    $clone_field_id = NULL;
    $values = $form_state->getValues();
    if (isset($values['default_value_input'])) {
      $default_value = [$values['default_value_input'][$clone_field]];
      $hide = $values['default_value_input']['_no_form_display'];
    }
    else {
      $default_value = [];
      $hide = FALSE;
    }
    if ($bundle) {
      $clone_field_definition = $this->getFieldDefinition($entity_type, $bundle, $clone_field);
      if (!$clone_field_definition instanceof BaseFieldDefinition) {
        $clone_field_id = $clone_field_definition->id();
      }
    }
    $cloned_field = $this->fieldManager->cloneField($this->entity, $clone_field, $clone_field_id, $default_value, $hide);
    $this->entity->setFieldMap([$cloned_field->getName() => $clone_field]);
    $this->entity->save();
  }

  /**
   * Get the widget that should used for the default value.
   *
   * Returns null to use the default for the field.
   * @todo This is in here specifically to look at a solution for Workbench Moderation.
   *       Should this be function on the runner plugin?
   *       Or an old school alter hook?
   * @param $definition
   *
   * @return WidgetBase|null
   */
  protected function getWidgetOverride(FieldDefinitionInterface $definition) {
    if ($definition->getType() == 'entity_reference'
      && $definition->getSetting('target_type') == 'moderation_state') {
      $definition->setRequired(FALSE);
      $definition->setDescription('');
      return \Drupal::service('plugin.manager.field.widget')->getInstance(array('field_definition' => $definition));
    }
    return NUll;
  }


}
