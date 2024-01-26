<?php

namespace AntiMattr\Tests\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\Exception\NoMigrationsToExecuteException;
use AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException;
use AntiMattr\MongoDB\Migrations\Migration;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    private $configuration;
    private $migration;
    private $outputWriter;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->outputWriter = $this->createMock('AntiMattr\MongoDB\Migrations\OutputWriter');

        $this->configuration->expects($this->once())
            ->method('getOutputWriter')
            ->willReturn($this->outputWriter);

        $this->migration = new Migration($this->configuration);
    }

    public function testMigrateThrowsUnknownVersionException()
    {
        $this->expectException(UnknownVersionException::class);
        $this->migration->migrate('1');
    }

    public function testMigrateHasNothingOutstanding()
    {
        $this->configuration->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn('1');

        $expectedMigrations = [
            '1' => 'foo',
        ];

        $this->configuration->expects($this->once())
            ->method('getMigrations')
            ->willReturn($expectedMigrations);

        $this->outputWriter->expects($this->never())
            ->method('write');

        $this->migration->migrate('1');
    }

    public function testMigrateButNoMigrationsFound()
    {
        $this->expectException(NoMigrationsToExecuteException::class);
        $this->configuration->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn('1');

        $expectedMigrations = [
            '0' => 'foo',
            '1' => 'foo',
            '2' => 'foo',
        ];

        $this->configuration->expects($this->once())
            ->method('getMigrations')
            ->willReturn($expectedMigrations);

        $this->outputWriter->expects($this->once())
            ->method('write');

        $this->migration->migrate('2');
    }

    public function testMigrate()
    {
        $this->configuration->expects($this->once())
            ->method('getLatestVersion')
            ->willReturn('2');

        $this->configuration->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn('1');

        $expectedMigrations = [
            '0' => 'foo',
            '1' => 'foo',
            '2' => 'foo',
        ];

        $this->configuration->expects($this->once())
            ->method('getMigrations')
            ->willReturn($expectedMigrations);

        $version = $this->createMock('AntiMattr\MongoDB\Migrations\Version');

        $this->configuration->expects($this->once())
            ->method('getMigrationsToExecute')
            ->willReturn(['2' => $version]);

        $this->outputWriter->expects($this->exactly(4))
            ->method('write');

        $this->migration->migrate();
    }
}
