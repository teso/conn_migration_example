<?php

namespace unit\Package\Database;

use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;

class DatabaseUseStatisticsTest extends Unit
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset singleton state after every test
        DatabaseUseStatistics::getInstance()->reset();
    }

    public function testGetInstance()
    {
        $instance = DatabaseUseStatistics::getInstance();

        $this->assertInstanceOf(DatabaseUseStatistics::class, $instance);
        $this->assertSame($instance, DatabaseUseStatistics::getInstance()); // It's singleton
    }

    public function testIncrementReadEndpointCounter()
    {
        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());

        DatabaseUseStatistics::getInstance()->incrementReadEndpointCounter();

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
    }

    public function testIncrementWriteEndpointCounter()
    {
        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());

        DatabaseUseStatistics::getInstance()->incrementWriteEndpointCounter();

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());
    }

    public function testGetReadEndpointCounter()
    {
        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
    }

    public function testGetWriteEndpointCounter()
    {
        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());
    }

    public function testReset()
    {
        DatabaseUseStatistics::getInstance()->incrementReadEndpointCounter();
        DatabaseUseStatistics::getInstance()->incrementWriteEndpointCounter();

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());

        DatabaseUseStatistics::getInstance()->reset();

        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());
    }
}
