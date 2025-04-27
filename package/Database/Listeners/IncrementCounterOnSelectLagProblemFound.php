<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Logger\LoggerTrait;
use Throwable;

class IncrementCounterOnSelectLagProblemFound
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(SelectLagProblemFound $event): void
    {
        try {
            DatabaseUseStatistics::getInstance()->incrementSelectLagProblemCounter();
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot increment lag problem conter',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
