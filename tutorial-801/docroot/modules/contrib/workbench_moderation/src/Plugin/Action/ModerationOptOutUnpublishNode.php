<?php

namespace Drupal\workbench_moderation\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Plugin\Action\UnpublishNode;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alternate action plugin that knows to opt-out of modifying moderated entites.
 *
 * @see UnpublishNode
 */
class ModerationOptOutUnpublishNode extends UnpublishNode implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $mod_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moderationInfo = $mod_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
   return new static(
     $configuration, $plugin_id, $plugin_definition,
     $container->get('workbench_moderation.moderation_information')
   );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity && $this->moderationInfo->isModeratableEntity($entity)) {
      drupal_set_message($this->t('One or more entities were skipped as they are under moderation and may not be directly published or unpublished.'));
      return;
    }

    parent::execute($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($object, $account, TRUE)
      ->andif(AccessResult::forbiddenIf($this->moderationInfo->isModeratableEntity($object))->addCacheableDependency($object));

    return $return_as_object ? $result : $result->isAllowed();
  }
}
