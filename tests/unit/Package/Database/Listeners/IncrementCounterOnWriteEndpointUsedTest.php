<?php

namespace unit\Package\Database\Listeners;

use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\WriteEndpointUsed;
use Package\Database\Listeners\IncrementCounterOnWriteEndpointUsed;

class IncrementCounterOnWriteEndpointUsedTest extends Unit
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset singleton state after every test
        DatabaseUseStatistics::getInstance()->reset();
    }

    public function testHandle()
    {
        $eventMock = $this->createMock(WriteEndpointUsed::class);
        $listener = new IncrementCounterOnWriteEndpointUsed();

        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());

        $listener->handle($eventMock);

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getWriteEndpointCounter());
    }
}
