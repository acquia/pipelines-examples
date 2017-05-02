<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Entity\Form\ScheduledUpdateForm.
 */

namespace Drupal\scheduled_updates\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Scheduled update edit forms.
 *
 * @ingroup scheduled_updates
 */
class ScheduledUpdateForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\scheduled_updates\Entity\ScheduledUpdate */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->langcode->value,
      '#languages' => Language::STATE_ALL,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Scheduled update.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Scheduled update.', [
          '%label' => $entity->label(),
        ]));
    }
    if ($entity->access('edit')) {
      $form_state->setRedirect('entity.scheduled_update.edit_form', ['scheduled_update' => $entity->id()]);
    }
    // @todo where to redirect if no entity access.
  }

}
