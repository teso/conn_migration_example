<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Package\Database\DatabaseUseStatistics;
use Package\Database\Events\SelectFallbackUsed;
use Package\Database\Logger\LoggerTrait;
use Throwable;

class IncrementCounterOnSelectFallbackUsed
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(SelectFallbackUsed $event): void
    {
        try {
            DatabaseUseStatistics::getInstance()->incrementSelectFallbackUseCounter();
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot increment fallback use conter',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
