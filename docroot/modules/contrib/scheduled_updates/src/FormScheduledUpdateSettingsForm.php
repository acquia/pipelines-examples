<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Entity\Form\ScheduledUpdateSettingsForm.
 */

namespace Drupal\scheduled_updates\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ScheduledUpdateSettingsForm.
 *
 * @package Drupal\scheduled_updates\Form
 *
 * @ingroup scheduled_updates
 */
class ScheduledUpdateSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ScheduledUpdate_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for Scheduled update entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ScheduledUpdate_settings']['#markup'] = 'Settings form for Scheduled update entities. Manage field settings here.';
    return $form;
  }

}
