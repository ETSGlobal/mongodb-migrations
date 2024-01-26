<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Collection;

use AntiMattr\MongoDB\Migrations\Collection\Statistics;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase
{
    private $collection;
    private $statistics;

    protected function setUp(): void
    {
        $this->collection = $this->createMock('MongoDB\Collection');
        $this->statistics = new Statistics();
    }

    public function testSetGetCollection()
    {
        $this->statistics->setCollection($this->collection);
        $this->assertEquals($this->collection, $this->statistics->getCollection());
    }

    public function testGetCollectionStatsThrowsExceptionWhenDataNotFound()
    {
        $this->expectException(\Exception::class);
        $database = $this->createMock('MongoDB\Database');

        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);
        $this->statistics->setDatabase($database);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->willReturn('example');

        $this->statistics->doGetCollectionStats();
    }

    public function testGetCollectionStats()
    {
        $database = $this->createMock('MongoDB\Database');

        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);
        $this->statistics->setDatabase($database);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->willReturn('example');

        $expectedData = [
            'count' => 100,
        ];

        $database->expects($this->once())
            ->method('command')
            ->willReturn($expectedData);

        $data = $this->statistics->doGetCollectionStats();

        $this->assertSame($expectedData, $data);
    }
}

class StatisticsStub extends Statistics
{
    public function doGetCollectionStats()
    {
        return $this->getCollectionStats();
    }
}
