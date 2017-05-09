<?php

/**
 * @file
 * Contains Drupal\scheduled_updates\Controller\ScheduledUpdateAddController.
 */

namespace Drupal\scheduled_updates\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\scheduled_updates\ScheduledUpdateTypeInterface;
use Drupal\scheduled_updates\UpdateRunnerUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class ScheduledUpdateAddController.
 *
 * @package Drupal\scheduled_updates\Controller
 */
class ScheduledUpdateAddController extends ControllerBase {

  /** @var \Drupal\Core\Entity\EntityStorageInterface  */
  protected $typeStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\scheduled_updates\UpdateRunnerUtils
   */
  protected $runnerUtils;

    public function __construct(EntityTypeManagerInterface $entityTypeManager, UpdateRunnerUtils $runnerUtils) {
      $this->storage = $entityTypeManager->getStorage('scheduled_update');
      $this->typeStorage = $entityTypeManager->getStorage('scheduled_update_type');
      $this->runnerUtils = $runnerUtils;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('entity_type.manager'),
        $container->get('scheduled_updates.update_runner')
      );
    }
    /**
     * Displays add links for available bundles/types for entity
     * scheduled_update .
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request object.
     *
     * @return array
     *   A render array for a list of the scheduled_update bundles/types that
     *   can be added or if there is only one type/bunlde defined for the site,
     *   the function returns the add page for that bundle/type.
     */
    public function add(Request $request) {
      $types = $this->getIndependentTypes();
      if ($types && count($types) == 1) {
        $type = reset($types);
        return $this->addForm($type, $request);
      }
      if (count($types) === 0) {
        return array(
          '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
            '%bundle' => 'Scheduled update',
            '@link' => $this->l($this->t('Go to the type creation page'), Url::fromRoute('entity.scheduled_update_type.add_form')),
          ]),
        );
      }
      return array('#theme' => 'scheduled_update_content_add_list', '#content' => $types);
    }

    /**
     * Presents the creation form for scheduled_update entities of given
     * bundle/type.
     *
     * @param EntityInterface $scheduled_update_type
     *   The custom bundle to add.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request object.
     *
     * @return array
     *   A form array as expected by drupal_render().
     */
    public function addForm(EntityInterface $scheduled_update_type, Request $request) {
      $entity = $this->storage->create(array(
        'type' => $scheduled_update_type->id()
      ));
      return $this->entityFormBuilder()->getForm($entity);
    }

    /**
     * Provides the page title for this controller.
     *
     * @param EntityInterface $scheduled_update_type
     *   The custom bundle/type being added.
     *
     * @return string
     *   The page title.
     */
    public function getAddFormTitle(EntityInterface $scheduled_update_type) {
      return t('Create <em>@label</em> Scheduled Update',
        array('@label' => $scheduled_update_type->label())
      );
    }

  protected function getIndependentTypes() {
    $types = [];
    /** @var ScheduledUpdateTypeInterface $type */
    foreach ($this->typeStorage->loadMultiple() as $type) {
      if ($this->runnerUtils->isIndependentUpdater($type)) {
        $types[] = $type;
      }
    }
    return $types;
  }

  /**
   * Determine access to update add page.
   *
   * If user has permission to add any types they should have access to this page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function addPageAccess(AccountInterface $account) {
    $types = $this->typeStorage->loadMultiple();
    $perms = [];
    foreach ($types as $type_id => $type) {
      $perms[] = "create $type_id scheduled updates";
    }
    return AccessResult::allowedIfHasPermissions($account, $perms, 'OR');
  }

  public function addFormAccess(AccountInterface $account, ScheduledUpdateTypeInterface $scheduled_update_type) {
    if ($scheduled_update_type->isEmbeddedType()) {
      return AccessResult::forbidden();
    }
    $type_id = $scheduled_update_type->id();
    return AccessResult::allowedIfHasPermission($account, "create $type_id scheduled updates");
  }
}
