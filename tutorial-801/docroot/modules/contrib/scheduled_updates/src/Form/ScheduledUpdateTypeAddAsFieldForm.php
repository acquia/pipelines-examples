<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Form\ScheduledUpdateTypeAddAsFieldForm.
 */

namespace Drupal\scheduled_updates\Form;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\scheduled_updates\Entity\ScheduledUpdateType;

/**
 * Class ScheduledUpdateTypeAddAsFieldForm.
 *
 * @package Drupal\scheduled_updates\Form
 */
class ScheduledUpdateTypeAddAsFieldForm extends ScheduledUpdateTypeBaseForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['update_entity_type'] = [
      '#type' => 'value',
      '#value' => $this->getCurrentEntityType($form_state),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->getCurrentEntityType($form_state);
    $values = $form_state->cleanValues()->getValues();
    $bundle = $form_state->get('target_bundle');
    $clone_field = $values['clone_field'];
    $clone_field_id = NULL;
    // When adding a field we don't expose the Label and Id of Bundle itself.
    $type_label = $this->entityLabel($entity_type) . ' - ' . $this->getFieldLabel($entity_type, $bundle, $clone_field);
    $this->entity->set('label', $type_label);
    $this->entity->set('id', $this->createNewUpdateTypeName($entity_type, $clone_field));
    parent::doSave($form, $form_state);

    $this->cloneSingleField($entity_type, $clone_field, $form_state, $bundle);

    $bundle_type = $this->entityTypeManager->getDefinition($entity_type)
      ->getBundleEntityType();
    $form_state->setRedirectUrl(Url::fromRoute("entity.entity_form_display.$entity_type.default", array(
      $bundle_type => $form_state->get('target_bundle'),
    )));

  }


  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $mode = 'independent', $bundle = '') {
    $form_state->set('target_entity_type_id', $entity_type_id);
    $form_state->set('target_bundle', $bundle);
    $this->entity->setUpdateEntityType($entity_type_id);

    return parent::buildForm($form, $form_state);
  }


  public function afterBuild(array $form, FormStateInterface $form_state) {
    $form = parent::afterBuild($form, $form_state);
    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#name'] == 'clone_field' && $clone_field = $form_state->getValue('clone_field')) {
      $new_field_element = &$form['type_dependent_elements']['reference_settings']['new_field'];
      $selected_field_label = $form['type_dependent_elements']['clone_field']['#options'][$clone_field];
      $new_field_element['label']['#value'] = $selected_field_label . ' ' . $this->t('Update');
      $new_field_element['field_name']['#value'] = '';
    }


    return $form;
  }

  /**
   * Create an update type name programmatically.
   *
   * @param $entity_type
   * @param $clone_field
   *
   * @return string
   */
  protected function createNewUpdateTypeName($entity_type, $clone_field) {
    $name = $entity_type . '__' . $clone_field;
    $suffix = 0;
    $new_name = $name;
    while (ScheduledUpdateType::load($new_name)) {
      $suffix++;
      $new_name = $name . '_' . $suffix;
    }
    return $new_name;
  }

  /**
   * {@inheritdoc}
   *
   * Override to only return runners that support embedded updates.
   */
  protected function getSupportedRunnerDefinitions() {
    $definitions = parent::getSupportedRunnerDefinitions();
    $supported_definitions = [];
    foreach ($definitions as $id => $definition) {
      if (in_array('embedded', $definition['update_types'])) {
        $supported_definitions[$id] = $definition;
      }
    }
    return $supported_definitions;
  }

  /**
   *{@inheritdoc}
   */
  protected function getDefaultBundle($field_selected, FormStateInterface $form_state) {
    return $form_state->get('target_bundle');
  }

}
