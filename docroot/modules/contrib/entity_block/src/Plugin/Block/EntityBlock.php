<?php

namespace Drupal\entity_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the block for similar articles.
 *
 * @Block(
 *   id = "entity_block",
 *   admin_label = @Translation("Entity block"),
 *   deriver = "Drupal\entity_block\Plugin\Derivative\EntityBlock"
 * )
 */
class EntityBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The name of our entity type.
   *
   * @var string
   */
  protected $entityTypeName;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The entity storage for our entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
  protected $entityStorage;

  /**
   * The view builder for our entity type.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityViewBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Determine what entity type we are referring to.
    $this->entityTypeName = $this->getDerivativeId();

    // Load various utilities related to our entity type.
    $this->entityTypeManager = $entityTypeManager;
    $this->entityStorage = $entityTypeManager->getStorage($this->entityTypeName);

    // Panelizer replaces the view_builder handler, but we want to use the
    // original which has been moved to fallback_view_builder.
    if ($entityTypeManager->hasHandler($this->entityTypeName, 'fallback_view_builder')) {
      $this->entityViewBuilder = $entityTypeManager->getHandler($this->entityTypeName, 'fallback_view_builder');
    }
    else {
      $this->entityViewBuilder = $entityTypeManager->getHandler($this->entityTypeName, 'view_builder');
    }

    $this->view_mode_options = $entityDisplayRepository->getViewModeOptions($this->entityTypeName);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $form['entity'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Entity'),
      '#target_type' => $this->entityTypeName,
      '#required' => TRUE,
    ];

    if (isset($config['entity'])) {
      if ($entity = $this->entityStorage->load($config['entity'])) {
        $form['entity']['#default_value'] = $entity;
      }
    }

    $view_mode = isset($config['view_mode']) ? $config['view_mode'] : NULL;

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $this->view_mode_options,
      '#default_value' => $view_mode,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Hide default block form fields that are undesired in this case.
    $form['admin_label']['#access'] = FALSE;
    $form['label']['#access'] = FALSE;
    $form['label_display']['#access'] = FALSE;

    // Hide the block title by default.
    $form['label_display']['#value'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['entity'] = $form_state->getValue('entity');
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($entity = $this->getEntity()) {
      $view_mode = isset($this->configuration['view_mode']) ? $this->configuration['view_mode'] : 'default';
      return $this->entityViewBuilder->view($entity, $view_mode);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($entity = $this->getEntity()) {
      return $this->entityTypeManager
        ->getAccessControlHandler($this->entityTypeName)
        ->access($entity, 'view', $account, TRUE);
    }
    else {
      return parent::blockAccess($account);
    }
  }

  /**
   * Returns the entity to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity to display, or NULL if none is configured.
   */
  protected function getEntity() {
    if ($entity_id = $this->configuration['entity']) {
      return $this->entityStorage->load($entity_id);
    }
    else {
      return NULL;
    }
  }

}
