<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\VersionCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * @author Ryan Catlin <ryan.catlin@gmail.com>
 */
class VersionCommandTest extends TestCase
{
    private $command;
    private $output;
    private $config;
    private $migration;
    private $version;

    protected function setUp(): void
    {
        $this->command = new VersionCommandStub();
        $this->output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->config = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->migration = $this->createMock('AntiMattr\MongoDB\Migrations\Migration');
        $this->version = $this->createMock('AntiMattr\MongoDB\Migrations\Version');

        $this->command->setMigrationConfiguration($this->config);
        $this->command->setMigration($this->migration);
    }

    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            [
                VersionCommand::getDefaultName(),
                $numVersion,
            ]
        );

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testUnknownVersionException()
    {
        $this->expectException(UnknownVersionException::class);

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            [
                VersionCommand::getDefaultName(),
                $numVersion,
                '--add',
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->willReturn(false)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testAddVersion()
    {
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            [
                VersionCommand::getDefaultName(),
                $numVersion,
                '--add',
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->willReturn(true)
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->willReturn($this->version)
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->willReturn(false)
        ;

        $this->version->expects($this->once())
            ->method('markMigrated')
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testDownVersion()
    {
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            [
                VersionCommand::getDefaultName(),
                $numVersion,
                '--delete',
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->willReturn(true)
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->willReturn($this->version)
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->willReturn(true)
        ;

        $this->version->expects($this->once())
            ->method('markNotMigrated')
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testDownOnNonMigratedVersionThrowsInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            [
                VersionCommand::getDefaultName(),
                $numVersion,
                '--delete',
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->willReturn(true)
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->willReturn($this->version)
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->willReturn(false)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testUpOnMigratedVersionThrowsInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            [
                VersionCommand::getDefaultName(),
                $numVersion,
                '--add',
            ]
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->willReturn(true)
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->willReturn($this->version)
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->willReturn(true)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}

class VersionCommandStub extends VersionCommand
{
    private $migration;

    public function setMigration(Migration $migration)
    {
        $this->migration = $migration;
    }

    protected function createMigration(Configuration $configuration)
    {
        return $this->migration;
    }
}
