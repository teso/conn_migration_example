<?php

namespace unit\Package\Database\Listeners;

use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Listeners\IncrementCounterOnSelectLagProblemFound;

class IncrementCounterOnSelectLagProblemFoundTest extends Unit
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset singleton state after every test
        DatabaseUseStatistics::getInstance()->reset();
    }

    public function testHandle()
    {
        $eventMock = $this->createMock(SelectLagProblemFound::class);
        $listener = new IncrementCounterOnSelectLagProblemFound();

        $this->assertSame(0, DatabaseUseStatistics::getInstance()->getSelectLagProblemCounter());

        $listener->handle($eventMock);

        $this->assertSame(1, DatabaseUseStatistics::getInstance()->getSelectLagProblemCounter());
    }
}
