<?php

/**
 * @file
 * Contains \Drupal\scheduled_updates\Command\RunUpdatesCommand.
 */

namespace Drupal\scheduled_updates\Command;

use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\scheduled_updates\UpdateRunnerUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunUpdatesCommand.
 *
 * @package Drupal\scheduled_updates
 */
class RunUpdatesCommand extends ContainerAwareCommand {
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('scheduled_updates:run_updates')
      ->setDescription($this->trans('command.scheduled_updates.run_updates.description'))
      ->addArgument('update_types', InputArgument::OPTIONAL, $this->trans('command.scheduled_updates.run_updates.arguments.update_types'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    /** @var UpdateRunnerUtils $runnerUtils */
    if ($runnerUtils = $this->hasGetService('scheduled_updates.update_runner')) {
      $update_types = $input->getArgument('update_types');
      if ($update_types) {
        $update_types = explode(',', $update_types);
      }
      else {
        $update_types = [];
      }
      $runnerUtils->runAllUpdates($update_types);
      $io->info('Updates run!');
    }
    else {
      $io->error('Could not get Global Runner service. No updates run.');
    }

  }

}
