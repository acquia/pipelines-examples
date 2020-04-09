<?php
/**
 * @file
 * Contains \Drupal\workbench_moderation\Plugin\IPEAccess\WorkbenchModerationIPEAccess.php
 */

namespace Drupal\workbench_moderation\Plugin\IPEAccess;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels_ipe\Annotation\IPEAccess;
use Drupal\panels_ipe\Plugin\IPEAccessBase;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @IPEAccess(
 *   id = "workbench_moderation_ipe",
 *   label = @Translation("Workbench moderation")
 * )
 */
class WorkbenchModerationIPEAccess extends IPEAccessBase implements ContainerFactoryPluginInterface {

  /**
   * The moderation information service.
   *
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $information;

  /**
   * WorkbenchModerationIPEAccess constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $information
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $information) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->information = $information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('workbench_moderation.moderation_information'));
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PanelsDisplayVariant $display) {
    if (!empty($display->getContexts()['@panelizer.entity_context:entity']) && $display->getContexts()['@panelizer.entity_context:entity']->hasContextValue()) {
      $entity = $display->getContexts()['@panelizer.entity_context:entity']->getContextValue();
      return $this->information->isModeratableEntity($entity);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(PanelsDisplayVariant $display) {
    $entity = $display->getContexts()['@panelizer.entity_context:entity']->getContextValue();
    return $this->information->isLatestRevision($entity) && !$this->information->isLiveRevision($entity);
  }

}
