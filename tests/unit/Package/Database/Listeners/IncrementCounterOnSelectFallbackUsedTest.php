<?php

namespace unit\Package\Database\Listeners;

use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\SelectFallbackUsed;
use Package\Database\Listeners\IncrementCounterOnSelectFallbackUsed;

class IncrementCounterOnSelectFallbackUsedTest extends Unit
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset singleton state after every test
        DatabaseUseStatistics::getInstance()->reset();
    }

    public function testHandle()
    {
        $eventMock = $this->createMock(SelectFallbackUsed::class);
        $listener = new IncrementCounterOnSelectFallbackUsed();

        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getSelectFallbackUseCounter());

        $listener->handle($eventMock);

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getSelectFallbackUseCounter());
    }
}
