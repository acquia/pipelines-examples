<?php
/**
 * @file
 * Contains Drupal\workbench_moderation\Entity\Handler\NodeCustomizations.
 */

namespace Drupal\workbench_moderation\Entity\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Customizations for node entities.
 */
class NodeModerationHandler extends ModerationHandler {

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $default_revision, $published_state) {
    if ($this->shouldModerate($entity)) {
      parent::onPresave($entity, $default_revision, $published_state);
      // Only nodes have a concept of published.
      /** @var $entity Node */
      $entity->setPublished($published_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form['revision']['#disabled'] = TRUE;
    $form['revision']['#default_value'] = TRUE;
    $form['revision']['#description'] = $this->t('Revisions are required.');
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    /* @var \Drupal\node\Entity\NodeType $entity */
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity->getThirdPartySetting('workbench_moderation', 'enabled', FALSE)) {
      // Force the revision checkbox on.
      $form['workflow']['options']['#default_value']['revision'] = 'revision';
      $form['workflow']['options']['revision']['#disabled'] = TRUE;
    }
  }

  /**
   * Check if an entity's default revision and/or state needs adjusting.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return bool
   *   TRUE when either the default revision or the state needs to be updated.
   */
  protected function shouldModerate(ContentEntityInterface $entity) {
    // First condition is needed so you can add a translation.
    // Second condition is needed when you want to publish a translation.
    // Third condition is needed when you want to create a new draft for a published translation.
    return $entity->isDefaultTranslation() || $entity->moderation_state->entity->isPublishedState() || $entity->isPublished();
  }

}
