<?php

namespace package\Qless\Listeners;

use Package\Database\DatabaseUseStatistics;
use Package\Database\Logger\LoggerTrait;
use Package\Qless\Listeners\Contracts\JobBeforePerformListener;
use Qless\Events\User\Job\BeforePerform;
use Throwable;

class ResetDatabaseUseStatisticsOnBeforePerform implements JobBeforePerformListener
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handler(BeforePerform $event): void
    {
        try {
            // Reset database use statistics before next job starts
            DatabaseUseStatistics::getInstance()->reset();
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot reset database use stats before job start',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
