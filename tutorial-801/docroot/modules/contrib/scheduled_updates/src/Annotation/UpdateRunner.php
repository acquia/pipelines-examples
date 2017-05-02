<?php

/**
 * @file
 * Contains \Drupal\image\Annotation\ImageEffect.
 */

namespace Drupal\scheduled_updates\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an update runner annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class UpdateRunner extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the update runner.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the update runner.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * Embedded or independent.
   *
   * @var array
   */
  public $update_types;

}
