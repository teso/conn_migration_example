<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\WriteEndpointUsed;
use Package\Database\Logger\LoggerTrait;
use Throwable;

class IncrementCounterOnWriteEndpointUsed
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(WriteEndpointUsed $event): void
    {
        try {
            DatabaseUseStatistics::getInstance()->incrementWriteEndpointCounter();
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot increment write conter',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
