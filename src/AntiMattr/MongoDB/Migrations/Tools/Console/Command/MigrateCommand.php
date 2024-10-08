<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Migration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
#[AsCommand(
    name: 'mongodb:migrations:migrate',
    description: 'Execute a migration to a specified version or the latest available version.',
)]
class MigrateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'The version to migrate to.', null)
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command executes a migration to a specified version or the latest available version:

    <info>%command.full_name%</info>

You can optionally manually specify the version you wish to migrate to:

    <info>%command.full_name% YYYYMMDDHHMMSS</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

EOT
        );

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface
     * @param \Symfony\Component\Console\Output\OutputInterface
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $input->getArgument('version');

        $configuration = $this->getMigrationConfiguration($input, $output);
        $migration = $this->createMigration($configuration);

        $this->outputHeader($configuration, $output);

        $noInteraction = !$input->isInteractive();

        $executedVersions = $configuration->getMigratedVersions();
        $availableVersions = $configuration->getAvailableVersions();
        $executedUnavailableVersions = array_diff($executedVersions, $availableVersions);

        if ($executedUnavailableVersions) {
            $output->writeln(sprintf('<error>WARNING! You have %s previously executed migrations in the database that are not registered migrations.</error>', count($executedUnavailableVersions)));
            foreach ($executedUnavailableVersions as $executedUnavailableVersion) {
                $output->writeln(
                    sprintf(
                        '    <comment>>></comment> %s (<comment>%s</comment>)',
                        Configuration::formatVersion($executedUnavailableVersion),
                        $executedUnavailableVersion
                    )
                );
            }

            if (!$noInteraction) {
                $question = new ConfirmationQuestion(
                    '<question>Are you sure you wish to continue? (y/[n])</question> ',
                    false
                );

                $confirmation = $this
                    ->getHelper('question')
                    ->ask($input, $output, $question);

                if (!$confirmation) {
                    $output->writeln('<error>Migration cancelled!</error>');

                    return parent::FAILURE;
                }
            }
        }

        // warn the user if no dry run and interaction is on
        if (!$noInteraction) {
            $question = new ConfirmationQuestion(
                '<question>WARNING! You are about to execute a database migration that could result in data lost. Are you sure you wish to continue? (y/[n])</question> ',
                false
            );

            $confirmation = $this
                ->getHelper('question')
                ->ask($input, $output, $question);

            if (!$confirmation) {
                $output->writeln('<error>Migration cancelled!</error>');

                return parent::FAILURE;
            }
        }

        $migration->migrate($version);

        return parent::SUCCESS;
    }

    protected function createMigration(Configuration $configuration)
    {
        return new Migration($configuration);
    }
}
