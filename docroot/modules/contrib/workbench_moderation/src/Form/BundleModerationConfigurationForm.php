<?php

namespace Drupal\workbench_moderation\Form;


use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workbench_moderation\Entity\ModerationState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring moderation usage on a given entity bundle.
 */
class BundleModerationConfigurationForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   *
   * We need to blank out the base form ID so that poorly written form alters
   * that use the base form ID to target both add and edit forms don't pick
   * up our form. This should be fixed in core.
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var ConfigEntityTypeInterface $bundle */
    $bundle = $form_state->getFormObject()->getEntity();
    $form['enable_moderation_state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable moderation states.'),
      '#description' => $this->t('Content of this type must transition through moderation states in order to be published.'),
      '#default_value' => $bundle->getThirdPartySetting('workbench_moderation', 'enabled', FALSE),
    ];

    // Add a special message when moderation is being disabled.
    if ($bundle->getThirdPartySetting('workbench_moderation', 'enabled', FALSE)) {
      $form['enable_moderation_state_note'] = [
        '#type' => 'item',
        '#description' => $this->t('After disabling moderation, any existing forward drafts will be accessible via the "Revisions" tab.'),
        '#states' => [
          'visible' => [
            ':input[name=enable_moderation_state]' => ['checked' => FALSE],
          ],
        ],
      ];
    }

    $states = $this->entityTypeManager->getStorage('moderation_state')->loadMultiple();
    $label = function(ModerationState $state) {
      return $state->label();
    };

    $options_published = array_map($label, array_filter($states, function(ModerationState $state) {
      return $state->isPublishedState();
    }));

    $options_unpublished = array_map($label, array_filter($states, function(ModerationState $state) {
      return !$state->isPublishedState();
    }));

    $form['allowed_moderation_states_unpublished'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed moderation states (Unpublished)'),
      '#description' => $this->t('The allowed unpublished moderation states this content-type can be assigned.'),
      '#default_value' => $bundle->getThirdPartySetting('workbench_moderation', 'allowed_moderation_states', array_keys($options_unpublished)),
      '#options' => $options_unpublished,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name=enable_moderation_state]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['allowed_moderation_states_published'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed moderation states (Published)'),
      '#description' => $this->t('The allowed published moderation states this content-type can be assigned.'),
      '#default_value' => $bundle->getThirdPartySetting('workbench_moderation', 'allowed_moderation_states', array_keys($options_published)),
      '#options' => $options_published,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name=enable_moderation_state]' => ['checked' => TRUE],
        ],
      ],
    ];

    // This is screwy, but the key of the array needs to be a user-facing string
    // so we have to fully render the translatable string to a real string, or
    // else PHP chokes on an object used as an array key.
    $options = [
      $this->t('Unpublished')->render() => $options_unpublished,
      $this->t('Published')->render() => $options_published,
    ];

    $form['default_moderation_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Default moderation state'),
      '#options' => $options,
      '#description' => $this->t('Select the moderation state for new content'),
      '#default_value' => $bundle->getThirdPartySetting('workbench_moderation', 'default_moderation_state', 'draft'),
      '#states' => [
        'visible' => [
          ':input[name=enable_moderation_state]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['#entity_builders'][] = [$this, 'formBuilderCallback'];

    return parent::form($form, $form_state);
  }

  /**
   * Form builder callback.
   *
   * @todo I don't know why this needs to be separate from the form() method.
   * It was in the form_alter version but we should see if we can just fold
   * it into the method above.
   *
   * @param $entity_type
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formBuilderCallback($entity_type, ConfigEntityInterface $bundle, &$form, FormStateInterface $form_state) {
    // @todo write a test for this.
    $bundle->setThirdPartySetting('workbench_moderation', 'enabled', $form_state->getValue('enable_moderation_state'));
    $bundle->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', array_keys(array_filter($form_state->getValue('allowed_moderation_states_published') + $form_state->getValue('allowed_moderation_states_unpublished'))));
    $bundle->setThirdPartySetting('workbench_moderation', 'default_moderation_state', $form_state->getValue('default_moderation_state'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('enable_moderation_state')) {
      $allowed = array_keys(array_filter($form_state->getValue('allowed_moderation_states_published') + $form_state->getValue('allowed_moderation_states_unpublished')));

      if (($default = $form_state->getValue('default_moderation_state')) && !in_array($default, $allowed, TRUE)) {
        $form_state->setErrorByName('default_moderation_state', $this->t('The default moderation state must be one of the allowed states.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // If moderation is enabled, revisions MUST be enabled as well.
    // Otherwise we can't have forward revisions.
    if($form_state->getValue('enable_moderation_state')) {
      /* @var ConfigEntityTypeInterface $bundle */
      $bundle = $form_state->getFormObject()->getEntity();

      $this->entityTypeManager->getHandler($bundle->getEntityType()->getBundleOf(), 'moderation')->onBundleModerationConfigurationFormSubmit($bundle);
    }

    parent::submitForm( $form, $form_state);

    drupal_set_message($this->t('Your settings have been saved.'));
  }
}
