<?php
/**
 * @file
 * Contains
 * \Drupal\scheduled_updates\Plugin\UpdateRunner\IndependentUpdateRunner.
 */


namespace Drupal\scheduled_updates\Plugin\UpdateRunner;


use Drupal\Core\Form\FormStateInterface;
use Drupal\scheduled_updates\Entity\ScheduledUpdate;
use Drupal\scheduled_updates\Plugin\BaseUpdateRunner;
use Drupal\scheduled_updates\Plugin\UpdateRunnerInterface;

/**
 * The default Embedded Update Runner.
 *
 * @UpdateRunner(
 *   id = "default_independent",
 *   label = @Translation("Default"),
 *   description = @Translation("Updates are created directly."),
 *   update_types = {"independent"}
 * )
 */
class IndependentUpdateRunner extends BaseUpdateRunner implements UpdateRunnerInterface {

  /**
   * Get all scheduled updates that referencing entities via Entity Reference
   * Field
   *
   *  @return ScheduledUpdate[]
   */
  protected function getReferencingUpdates() {
    $updates = [];
    $update_ids = $this->getReadyUpdateIds();
    foreach ($update_ids as $update_id) {
      $updates[] = [
        'update_id' => $update_id,
        'entity_type' => $this->updateEntityType(),
      ];
    }
    return $updates;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAllUpdates() {
    return $this->getReferencingUpdates();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    if ($update_type = $this->getUpdateType($form_state)) {
      $settings = $form_state->get('update_runner');
      $bundles = isset($settings['bundles'])? array_filter($settings['bundles']): [];
      if ($entity_type = $update_type->getUpdateEntityType()) {
        $form['bundles'] = [
          '#type' => 'checkboxes',
          '#title' => $this->bundleLabel($entity_type),
          '#options' => $this->updateUtils->bundleOptions($entity_type),
          '#default_value' => $bundles,
          '#required' => TRUE,
        ];
      }


    }
    return $form;
  }


}
