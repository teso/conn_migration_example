<?php

namespace unit\Package\Qless\Listeners;

use AspectMock\Test;
use Codeception\Test\Unit;
use Package\Database\MySqlConnection;
use package\Qless\Listeners\ResetDataModificationStateOnAfterPerform;
use Qless\Events\User\Job\AfterPerform;
use TEloquentDB;

class ResetDataModificationStateOnAfterPerformTest extends Unit
{
    public function testHandler()
    {
        $eventMock = $this->createMock(AfterPerform::class);
        $listener = new ResetDataModificationStateOnAfterPerform();
        $connectionMock = $this->createMock(MySqlConnection::class);
        Test::double(TEloquentDB::class, ['get' => fn () => $connectionMock]);

        $connectionMock
            ->expects($this->once())
            ->method('forgetRecordModificationState');

        $listener->handler($eventMock);

        Test::clean(TEloquentDB::class);
    }
}
