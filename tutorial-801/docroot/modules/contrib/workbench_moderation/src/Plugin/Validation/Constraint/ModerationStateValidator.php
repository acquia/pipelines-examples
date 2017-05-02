<?php

namespace Drupal\workbench_moderation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Drupal\workbench_moderation\StateTransitionValidation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ModerationStateValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The state transition validation.
   *
   * @var \Drupal\workbench_moderation\StateTransitionValidation
   */
  protected $validation;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The moderation info.
   *
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Creates a new ModerationStateValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\workbench_moderation\StateTransitionValidation $validation
   *   The state transition validation.
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateTransitionValidation $validation, ModerationInformationInterface $moderation_information) {
    $this->validation = $validation;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workbench_moderation.state_transition_validation'),
      $container->get('workbench_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();

    // Ignore entities that are not subject to moderation anyway.
    if (!$this->moderationInformation->isModeratableEntity($entity)) {
      return;
    }

    // Ignore entities that are being created for the first time.
    if ($entity->isNew()) {
      return;
    }

    // Ignore entities that are being moderated for the first time, such as
    // when they existed before moderation was enabled for this entity type.
    if ($this->isFirstTimeModeration($entity)) {
      return;
    }

    $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    if (!$entity->isDefaultTranslation() && $original_entity->hasTranslation($entity->language()->getId())) {
      $original_entity = $original_entity->getTranslation($entity->language()->getId());
    }
    $next_moderation_state_id = $entity->moderation_state->target_id;
    $original_moderation_state_id = $original_entity->moderation_state->target_id;

    if (!$this->validation->isTransitionAllowed($original_moderation_state_id, $next_moderation_state_id)) {
      $this->context->addViolation($constraint->message, ['%from' => $original_entity->moderation_state->entity->label(), '%to' => $entity->moderation_state->entity->label()]);
    }
  }

  /**
   * Determines if this entity is being moderated for the first time.
   *
   * If the previous version of the entity has no moderation state, we assume
   * that means it predates the presence of moderation states.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   *   TRUE if this is the entity's first time being moderated, FALSE otherwise.
   */
  protected function isFirstTimeModeration(EntityInterface $entity) {
    $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());

    $original_id = $original_entity->moderation_state->target_id;

    return !($entity->moderation_state->target_id && $original_entity && $original_id);
  }

}
