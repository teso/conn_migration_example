<?php

namespace unit\Package\Database\Listeners;

use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\ReadEndpointUsed;
use Package\Database\Listeners\IncrementCounterOnReadEndpointUsed;

class IncrementCounterOnReadEndpointUsedTest extends Unit
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset singleton state after every test
        DatabaseUseStatistics::getInstance()->reset();
    }

    public function testHandle()
    {
        $eventMock = $this->createMock(ReadEndpointUsed::class);
        $listener = new IncrementCounterOnReadEndpointUsed();

        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());

        $listener->handle($eventMock);

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getReadEndpointCounter());
    }
}
