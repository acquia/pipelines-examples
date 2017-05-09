<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Form\ScheduledUpdateTypeForm.
 */

namespace Drupal\scheduled_updates\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\scheduled_updates\ClassUtilsTrait;
use Drupal\scheduled_updates\FieldUtilsTrait;

/**
 * Class ScheduledUpdateTypeForm.
 *
 * @package Drupal\scheduled_updates\Form
 */
class ScheduledUpdateTypeForm extends ScheduledUpdateTypeBaseForm {

  use FieldUtilsTrait;

  use ClassUtilsTrait;



  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the Scheduled update type."),
      '#required' => TRUE,
      '#weight' => -110,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\scheduled_updates\Entity\ScheduledUpdateType::load',
      ),
      '#disabled' => !$this->entity->isNew(),
      '#weight' => -100,
    );


    $default_type = $this->getCurrentEntityType($form_state);

    $disabled = !$this->entity->isNew();
    $form['update_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('The entity type to update. This <strong>cannot</strong> be changed after this type is created.'),
      '#options' => $this->entityTypeOptions(),
      '#default_value' => $default_type,
      '#required' => TRUE,
      // @todo why doesn't this work?
      '#disabled' => $disabled,
      '#weight' => -90,
    ];
    // @todo Remove when bug is fixed.
    if (!$form['update_entity_type']['#disabled']) {
      // Just to duplicate issues on d.o for now.
      $form['update_entity_type']['#description'] .= '<br /><strong>**KNOWN BUG**</strong> : Ajax error when selecting one entity type and then selecting another: https://www.drupal.org/node/2643934';
    }
    $form['update_entity_type'] += $this->typeDependentAjax();


    $form['field_map'] = $this->createFieldMapElements();
    /* You will need additional form elements for your custom properties. */

    return $form;
  }


  /**
   * Create select element entity type options.
   *
   * @return array
   */
  protected function entityTypeOptions() {
    $options[''] = '';
    foreach ($this->entityTypeManager->getDefinitions() as $entity_id => $entityTypeInterface) {
      if ($entity_id == 'scheduled_update') {
        // Don't allow updating of updates! Inception!
        continue;
      }
      if (is_subclass_of($entityTypeInterface->getClass(), 'Drupal\Core\Entity\ContentEntityInterface')) {
        $options[$entity_id] = $entityTypeInterface->getLabel();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::doSave($form, $form_state);
    $clone_field = $form_state->getValue('clone_field');

    $multi_field = FALSE;
    if ($clone_field) {
      if ($clone_field != 'multiple-field') {
        $this->cloneSingleField($this->entity->getUpdateEntityType(), $clone_field, $form_state);
      }
      else {
        $multi_field = TRUE;
      }
    }


    switch ($status) {
      case SAVED_NEW:
        if ($multi_field) {
          drupal_set_message($this->t('Select fields to add to these updates'));
          $form_state->setRedirectUrl($this->entity->urlInfo('clone-fields'));
        }
        else {
          $form_state->setRedirectUrl(Url::fromRoute("entity.entity_form_display.scheduled_update.default", array(
            'scheduled_update_type' => $this->entity->id(),
          )));
        }
        break;

      default:
        drupal_set_message($this->t('Saved the %label Scheduled update type.', [
          '%label' => $this->entity->label(),
        ]));
        $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
    }

  }


  /**
   * Create form elements to update field map.
   *
   * @return array
   * @internal param array $form
   * @internal param $scheduled_update_type
   *
   */
  protected function createFieldMapElements() {
    if ($this->entity->isNew()) {
      return [];
    }
    $field_map_help = 'Select the destination fields for this update type.'
      . ' Not all field have to have destinations if you using them for other purposes.';
    $elements = [
      '#type' => 'details',
      '#title' => 'destination fields',
      '#description' => $this->t($field_map_help),
      '#tree' => TRUE,
    ];
    $source_fields = $this->getSourceFields($this->entity);

    $field_map = $this->entity->getFieldMap();

    foreach ($source_fields as $source_field_id => $source_field) {
      $destination_fields_options = $this->getDestinationFieldsOptions($this->entity->getUpdateEntityType(), $source_field);
      $elements[$source_field_id] = [
        '#type' => 'select',
        '#title' => $source_field->label(),
        '#options' => ['' => $this->t("(Not mapped)")] + $destination_fields_options,
        '#default_value' => isset($field_map[$source_field_id]) ? $field_map[$source_field_id] : '',
      ];
    }
    return $elements;
  }


  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $runner_settings = $form_state->getValue('update_runner');
    $update_runner = $this->createRunnerInstance($runner_settings, $form_state);
    $update_runner->validateConfigurationForm($form, $form_state);
  }

  /**
   * Overridden to provide multi-field choice.
   *
   * This option will route the user to the clone fields page.
   * @todo should this option always be available?
   *
   * {@inheritdoc}
   */
  protected function createCloneFieldSelect(array $form, FormStateInterface $form_state) {
    $elements = parent::createCloneFieldSelect($form, $form_state);
    if (isset($elements['clone_field']['#options'])) {
      $elements['clone_field']['#options']['multiple-field'] = '(' . $this->t('Create a multiple field update') . ')';
    }
    return $elements;
  }


}
