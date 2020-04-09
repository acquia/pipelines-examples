<?php

namespace Drupal\workbench_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Entity\ModerationStateTransition;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Stores the possible state transitions.
   *
   * @var array
   */
  protected $possibleTransitions = [];

  /**
   * Constructs a new StateTransitionValidation.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
  }

  /**
   * Computes a mapping of possible transitions.
   *
   * This method is uncached and will recalculate the list on every request.
   * In most cases you want to use getPossibleTransitions() instead.
   *
   * @see static::getPossibleTransitions()
   *
   * @return array[]
   *   An array containing all possible transitions. Each entry is keyed by the
   *   "from" state, and the value is an array of all legal "to" states based
   *   on the currently defined transition objects.
   */
  protected function calculatePossibleTransitions() {
    $transitions = $this->transitionStorage()->loadMultiple();

    $possible_transitions = [];
    /** @var \Drupal\workbench_moderation\ModerationStateTransitionInterface $transition */
    foreach ($transitions as $transition) {
      $possible_transitions[$transition->getFromState()][] = $transition->getToState();
    }
    return $possible_transitions;
  }

  /**
   * Returns a mapping of possible transitions.
   *
   * @return array[]
   *   An array containing all possible transitions. Each entry is keyed by the
   *   "from" state, and the value is an array of all legal "to" states based
   *   on the currently defined transition objects.
   */
  protected function getPossibleTransitions() {
    if (empty($this->possibleTransitions)) {
      $this->possibleTransitions = $this->calculatePossibleTransitions();
    }
    return $this->possibleTransitions;
  }

  /**
   * Gets a list of states a user may transition an entity to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be transitioned.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account that wants to perform a transition.
   *
   * @return ModerationState[]
   *   Returns an array of States to which the specified user may transition the
   *   entity.
   */
  public function getValidTransitionTargets(ContentEntityInterface $entity, AccountInterface $user) {
    $bundle = $this->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());

    $states_for_bundle = $bundle->getThirdPartySetting('workbench_moderation', 'allowed_moderation_states', []);

    /** @var ModerationState $state */
    $state = $entity->moderation_state->entity;
    $current_state_id = $state->id();

    $all_transitions = $this->getPossibleTransitions();
    $destinations = $all_transitions[$current_state_id];

    $destinations = array_intersect($states_for_bundle, $destinations);

    $permitted_destinations = array_filter($destinations, function($state_name) use ($current_state_id, $user) {
      return $this->userMayTransition($current_state_id, $state_name, $user);
    });

    return $this->entityTypeManager->getStorage('moderation_state')->loadMultiple($permitted_destinations);
  }

  /**
   * Gets a list of transitions that are legal for this user on this entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be transitioned.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account that wants to perform a transition.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return ModerationStateTransition[]
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $bundle = $this->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());

    /** @var ModerationState $current_state */
    $current_state = $entity->moderation_state->entity;
    $current_state_id = $current_state ? $current_state->id(): $bundle->getThirdPartySetting('workbench_moderation', 'default_moderation_state');

    // Determine the states that are legal on this bundle.
    $legal_bundle_states = $bundle->getThirdPartySetting('workbench_moderation', 'allowed_moderation_states', []);

    // Legal transitions include those that are possible from the current state,
    // filtered by those whose target is legal on this bundle and that the
    // user has access to execute.
    $transitions = array_filter($this->getTransitionsFrom($current_state_id), function(ModerationStateTransition $transition) use ($legal_bundle_states, $user) {
      return in_array($transition->getToState(), $legal_bundle_states)
        && $user->hasPermission('use ' . $transition->id() . ' transition');
    });

    return $transitions;
  }

  /**
   * Returns a list of transitions from a given state.
   *
   * This list is based only on those transitions that exist, not what
   * transitions are legal in a given context.
   *
   * @param string $state_name
   *   The machine name of the state from which we are transitioning.
   *
   * @return ModerationStateTransition[]
   */
  protected function getTransitionsFrom($state_name) {
    $result = $this->transitionStateQuery()
      ->condition('stateFrom', $state_name)
      ->sort('weight')
      ->execute();

    return $this->transitionStorage()->loadMultiple($result);
  }

  /**
   * Determines if a user is allowed to transition from one state to another.
   *
   * This method will also return FALSE if there is no transition between the
   * specified states at all.
   *
   * @param string $from
   *   The origin state machine name.
   * @param string $to
   *   The desetination state machine name.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to validate.
   *
   * @return bool
   *   TRUE if the given user may transition between those two states.
   */
  public function userMayTransition($from, $to, AccountInterface $user) {
    if ($transition = $this->getTransitionFromStates($from, $to)) {
      return $user->hasPermission('use ' . $transition->id() . ' transition');
    }
    return FALSE;
  }

  /**
   * Returns the transition object that transitions from one state to another.
   *
   * @param string $from
   *   The name of the "from" state.
   * @param string $to
   *   The name of the "to" state.
   *
   * @return ModerationStateTransition|null
   *   A transition object, or NULL if there is no such transition in the system.
   */
  protected function getTransitionFromStates($from, $to) {
    $from = $this->transitionStateQuery()
      ->condition('stateFrom', $from)
      ->condition('stateTo', $to)
      ->execute();

    $transitions = $this->transitionStorage()->loadMultiple($from);

    if ($transitions) {
      return current($transitions);
    }
    return NULL;
  }

  /**
   * Determines a transition allowed.
   *
   * @param string $from
   *   The from state.
   * @param string $to
   *   The to state.
   *
   * @return bool
   *   Is the transition allowed.
   */
  public function isTransitionAllowed($from, $to) {
    $allowed_transitions = $this->calculatePossibleTransitions();
    if (isset($allowed_transitions[$from])) {
      return in_array($to, $allowed_transitions[$from], TRUE);
    }
    return FALSE;
  }

  /**
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A transition state query.
   */
  protected function transitionStateQuery() {
    return $this->queryFactory->get('moderation_state_transition', 'AND');
  }

  /**
   * Returns the transition entity storage service.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function transitionStorage() {
    return $this->entityTypeManager->getStorage('moderation_state_transition');
  }

  /**
   * Returns the state entity storage service.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function stateStorage() {
    return $this->entityTypeManager->getStorage('moderation_state');
  }

  /**
   * Loads a specific bundle entity.
   *
   * @param string $bundle_entity_type_id
   *   The bundle entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   */
  protected function loadBundleEntity($bundle_entity_type_id, $bundle_id) {
    if ($bundle_entity_type_id) {
      return $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_id);
    }
  }
}
