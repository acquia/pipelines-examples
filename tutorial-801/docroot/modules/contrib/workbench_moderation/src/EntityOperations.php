<?php

namespace Drupal\workbench_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\workbench_moderation\Event\WorkbenchModerationEvents;
use Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent;
use Drupal\workbench_moderation\Form\EntityModerationForm;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\workbench_moderation\RevisionTrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\workbench_moderation\RevisionTrackerInterface $tracker
   *   The revision tracker.
   */
  public function __construct(ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, EventDispatcherInterface $event_dispatcher, RevisionTrackerInterface $tracker) {
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->formBuilder = $form_builder;
    $this->tracker = $tracker;
  }

  /**
   * Hook bridge.
   *
   * @see hook_entity_storage_load().
   *
   * @param EntityInterface[] $entities
   *   An array of entity objects that have just been loaded.
   * @param string $entity_type_id
   *   The type of entity being loaded, such as "node" or "user".
   */
  public function entityStorageLoad(array $entities, $entity_type_id) {

    // Ensure that all moderatable entities always have a moderation_state field
    // with data, in all translations. That avoids us needing to have a thousand
    // NULL checks elsewhere in the code.

    // Quickly exclude any non-moderatable entities.
    $to_check = array_filter($entities, [$this->moderationInfo, 'isModeratableEntity']);
    if (!$to_check) {
      return;
    }

    // @todo make this more functional, less iterative.
    foreach ($to_check as $entity) {
      foreach ($entity->getTranslationLanguages() as $language) {
        $translation = $entity->getTranslation($language->getId());
        if ($translation->moderation_state->target_id == NULL) {
          $translation->moderation_state->target_id = $this->getDefaultLoadStateId($translation);
        }
      }
    }
  }

  /**
   * Determines the default moderation state on load for an entity.
   *
   * This method is only applicable when an entity is loaded that has
   * no moderation state on it, but should. In those cases, failing to set
   * one may result in NULL references elsewhere when other code tries to check
   * the moderation state of the entity.
   *
   * The amount of indirection here makes performance a concern, but
   * given how Entity API works I don't know how else to do it.
   * This reliably gets us *A* valid state. However, that state may be
   * not the ideal one. Suggestions on how to better select the default
   * state here are welcome.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which we want a default state.
   *
   * @return string
   *   The default state for the given entity.
   */
  protected function getDefaultLoadStateId(ContentEntityInterface $entity) {
    return $this->moderationInfo
      ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle())
      ->getThirdPartySetting('workbench_moderation', 'default_moderation_state');
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratableEntity($entity)) {
      return;
    }
    if ($entity->moderation_state->entity) {
      $published_state = $entity->moderation_state->entity->isPublishedState();

      // This entity is default if it is new, the default revision, or the
      // default revision is not published.
      $update_default_revision = $entity->isNew()
        || $entity->moderation_state->entity->isDefaultRevisionState()
        || !$this->isDefaultRevisionPublished($entity);

      // Fire per-entity-type logic for handling the save process.
      $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'moderation')->onPresave($entity, $update_default_revision, $published_state);

      // There's currently a bug in core where $entity->original always points
      // to the default revision, for now work around this by loading the latest
      // revision.
      $latest_revision = $this->moderationInfo->getLatestRevision($entity->getEntityTypeId(), $entity->id());
      $state_before = !empty($latest_revision) ? $latest_revision->moderation_state->target_id : NULL;
      // @todo: Revert to this simpler version when https://www.drupal.org/node/2700747 is fixed.
      // $state_before = isset($entity->original) ? $entity->original->moderation_state->target_id : NULL;

      $state_after = $entity->moderation_state->target_id;

      // Allow other modules to respond to the transition. Note that this
      // does not provide any mechanism to cancel the transition, since
      // Entity API doesn't allow hook_entity_presave to short-circuit a save.
      $event = new WorkbenchModerationTransitionEvent($entity, $state_before, $state_after);

      $this->eventDispatcher->dispatch(WorkbenchModerationEvents::STATE_TRANSITION, $event);
    }
  }

  /**
   * Hook bridge.
   *
   * @see hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   */
  public function entityInsert(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratableEntity($entity)) {
      return;
    }

    /** ContentEntityInterface $entity */

    // Update our own record keeping.
    $this->tracker->setLatestRevision($entity->getEntityTypeId(), $entity->id(), $entity->language()->getId(), $entity->getRevisionId());
  }

  /**
   * Hook bridge.
   *
   * @see hook_entity_update().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   */
  public function entityUpdate(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratableEntity($entity)) {
      return;
    }

    /** ContentEntityInterface $entity */

    // Update our own record keeping.
    $this->tracker->setLatestRevision($entity->getEntityTypeId(), $entity->id(), $entity->language()->getId(), $entity->getRevisionId());
  }

  /**
   * Act on entities being assembled before rendering.
   *
   * This is a hook bridge.
   *
   * @see hook_entity_view()
   * @see EntityFieldManagerInterface::getExtraFields()
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {

    if (!$this->moderationInfo->isModeratableEntity($entity)) {
      return;
    }
    if (!$this->moderationInfo->isLatestRevision($entity)) {
      return;
    }
    /** @var ContentEntityInterface $entity */
    if ($entity->isDefaultRevision()) {
      return;
    }

    $component = $display->getComponent('workbench_moderation_control');
    if ($component) {
      $build['workbench_moderation_control'] = $this->formBuilder->getForm(EntityModerationForm::class, $entity);
      $build['workbench_moderation_control']['#weight'] = $component['weight'];
    }
  }

  /**
   * Check if the default revision for the given entity is published.
   *
   * The default revision is the same as the entity retrieved by "default" from
   * the storage handler. If the entity is translated, use the default revision
   * of the same language as the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @return bool
   *   TRUE if the default revision is published. FALSE otherwise.
   */
  protected function isDefaultRevisionPublished(EntityInterface $entity) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $default_revision = $storage->load($entity->id());

    // Ensure we are comparing the same translation as the current entity.
    if ($default_revision instanceof TranslatableInterface && $default_revision->isTranslatable()) {
      // If there is no translation, then there is no default revision and is
      // therefore not published.
      if (!$default_revision->hasTranslation($entity->language()->getId())) {
        return FALSE;
      }

      $default_revision = $default_revision->getTranslation($entity->language()->getId());
    }

    return $default_revision && $default_revision->moderation_state->entity->isPublishedState();
  }

}
