<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Form\UpdateRunnerForm.
 */

namespace Drupal\scheduled_updates\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\scheduled_updates\UpdateRunnerUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UpdateRunnerForm.
 *
 * @package Drupal\scheduled_updates\Form
 */
class UpdateRunnerForm extends FormBase {

  /**
   * Drupal\scheduled_updates\UpdateRunnerUtils definition.
   *
   * @var \Drupal\scheduled_updates\UpdateRunnerUtils
   */
  protected $scheduled_updates_update_runner;

  public function __construct(
    UpdateRunnerUtils $scheduled_updates_update_runner
  ) {
    $this->scheduled_updates_update_runner = $scheduled_updates_update_runner;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('scheduled_updates.update_runner')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_runner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Updates'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->scheduled_updates_update_runner->runAllUpdates();
  }

}
