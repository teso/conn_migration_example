<?php

namespace package\Qless\Listeners;

use Package\Database\Logger\LoggerTrait;
use Package\Qless\Listeners\Contracts\JobAfterPerformListener;
use Qless\Events\User\Job\AfterPerform;
use TEloquentDB;
use Throwable;

class ResetDataModificationStateOnAfterPerform implements JobAfterPerformListener
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handler(AfterPerform $event): void
    {
        try {
            // Reset modification data state after job is complete
            TEloquentDB::get(TEloquentDB::CONNECTION_MAIN)->forgetRecordModificationState();
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot reset modification state after job complete',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
