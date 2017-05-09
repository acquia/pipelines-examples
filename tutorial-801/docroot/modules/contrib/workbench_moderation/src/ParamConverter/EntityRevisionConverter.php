<?php

namespace Drupal\workbench_moderation\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * EntityRevisionConverter constructor.
   *
   * @todo: If the parent class is ever cleaned up to use EntityTypeManager
   * instead of Entity manager, this method will also need to be adjusted.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_type_manager
   *   The entity manager, needed by the parent class.
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info utility service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_info) {
    parent::__construct($entity_type_manager);
    $this->moderationInformation = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $this->hasForwardRevisionFlag($definition)
      || $this->isEditFormPage($route)
      || $this->isQuickeditRoute($route);
  }

  /**
   * Determines if the route definition includes a forward-revision flag.
   *
   * This is a custom flag defined by WBM to load forward revisions rather than
   * the default revision on a given route.
   *
   * @param array $definition
   *   The parameter definition provided in the route options.
   *
   * @return bool
   *   TRUE if the forward revision flag is set, FALSE otherwise.
   */
  protected function hasForwardRevisionFlag(array $definition) {
    return (isset($definition['load_forward_revision']) && $definition['load_forward_revision']);
  }

  /**
   * Determines if a given route is the edit-form for an entity.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route definition.
   *
   * @return bool
   *   Returns TRUE if the route is the edit form of an entity, FALSE otherwise.
   */
  protected function isEditFormPage(Route $route) {
    if ($default = $route->getDefault('_entity_form') ) {
      // If no operation is provided, use 'default'.
      $default .= '.default';
      list($entity_type_id, $operation) = explode('.', $default);
      if (!$this->entityManager->hasDefinition($entity_type_id)) {
        return FALSE;
      }
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      return $operation == 'edit' && $entity_type && $entity_type->isRevisionable();
    }
  }

  /**
    * Determines if a given route is related to Quickedit.
    *
    * @param \Symfony\Component\Routing\Route $route
    *   The route definition.
    *
    * @return bool
    *   Returns TRUE if the route is related to Quickedit, FALSE otherwise.
    */
  protected function isQuickeditRoute(Route $route) {
    $compatible_routes = [
      '/quickedit/form/{entity_type}/{entity}/{field_name}/{langcode}/{view_mode_id}',
      '/quickedit/entity/{entity_type}/{entity}',
      '/editor/{entity_type}/{entity}/{field_name}/{langcode}/{view_mode_id}',
      '/quickedit/image/upload/{entity_type}/{entity}/{field_name}/{langcode}/{view_mode_id}',
      '/quickedit/image/info/{entity_type}/{entity}/{field_name}/{langcode}/{view_mode_id}'
    ];
    if (in_array($route->getPath(), $compatible_routes)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity = parent::convert($value, $definition, $name, $defaults);

    if ($entity && $this->moderationInformation->isModeratableEntity($entity) && !$this->moderationInformation->isLatestRevision($entity)) {
      $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
      $entity = $this->moderationInformation->getLatestRevision($entity_type_id, $value);

      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = $this->entityManager->getTranslationFromContext($entity, NULL, array('operation' => 'entity_upcast'));
      }
    }

    return $entity;
  }

}
