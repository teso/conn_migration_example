<?php

declare(strict_types=1);

namespace Package\Database\Logger;

use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    private function getLogger(): LoggerInterface
    {
        return logger('database');
    }
}
