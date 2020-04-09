<?php

/**
 * @file
 * Contains Drupal\scheduled_updates\Form\AdminForm.
 */

namespace Drupal\scheduled_updates\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminForm.
 *
 * @package Drupal\scheduled_updates\Form
 */
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'scheduled_updates.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduled_updates_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scheduled_updates.settings');
    $form['timeout'] = array(
      '#type' => 'number',
      '#title' => t('Timeout in seconds for running updates.'),
      '#default_value' => $config->get('timeout') ? $config->get('timeout') : 15,
      '#min' => 1,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('scheduled_updates.settings')
      ->set('timeout', $form_state->getValue('timeout'))
      ->save();
  }

}
