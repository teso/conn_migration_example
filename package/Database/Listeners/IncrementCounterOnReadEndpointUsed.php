<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\ReadEndpointUsed;
use Package\Database\Logger\LoggerTrait;
use Throwable;

class IncrementCounterOnReadEndpointUsed
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(ReadEndpointUsed $event): void
    {
        try {
            DatabaseUseStatistics::getInstance()->incrementReadEndpointCounter();
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot increment read conter',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
