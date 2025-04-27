<?php

namespace unit\Package\Database\Logger;

use Codeception\Test\Unit;
use Package\Database\Logger\LoggerTrait;
use Psr\Log\LoggerInterface;

class LoggerTraitTest extends Unit
{
    private const CHANNEL_NAME = 'database';

    public function testGetLogger()
    {
        $instance = new class {
            use LoggerTrait {
                getLogger as public;
            }
        };
        $logger = $instance->getLogger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertSame(self::CHANNEL_NAME, $logger->getName());
        $this->assertSame($logger, $instance->getLogger()); // It's singleton

    }
}
