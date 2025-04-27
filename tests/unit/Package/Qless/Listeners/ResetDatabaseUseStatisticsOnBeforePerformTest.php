<?php

namespace unit\Package\Qless\Listeners;

use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;
use package\Qless\Listeners\ResetDatabaseUseStatisticsOnBeforePerform;
use Qless\Events\User\Job\BeforePerform;

class ResetDatabaseUseStatisticsOnBeforePerformTest extends Unit
{
    private const READ_COUNTER = 2;
    private const WRITE_COUNTER = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCounters();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownCounters();
    }

    public function testHandler()
    {
        $jobMock = $this->createMock(BeforePerform::class);
        $listener = new ResetDatabaseUseStatisticsOnBeforePerform();

        $this->assertSame(self::READ_COUNTER, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
        $this->assertSame(self::WRITE_COUNTER, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());

        $listener->handler($jobMock);

        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());
    }

    private function setUpCounters(): void
    {
        for ($i = 0; $i < self::READ_COUNTER; $i++) {
            DatabaseUseStatistics::getInstance()->incrementReadEndpointCounter();
        }

        for ($i = 0; $i < self::WRITE_COUNTER; $i++) {
            DatabaseUseStatistics::getInstance()->incrementWriteEndpointCounter();
        }
    }

    private function tearDownCounters(): void
    {
        DatabaseUseStatistics::getInstance()->reset();
    }
}
