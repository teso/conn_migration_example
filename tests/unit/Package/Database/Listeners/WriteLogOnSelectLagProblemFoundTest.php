<?php

namespace unit\Package\Database\Listeners;

use AspectMock\Test;
use Codeception\Test\Unit;
use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Listeners\WriteLogOnSelectLagProblemFound;
use Package\Database\MySqlConnection;
use Package\Log\Log;
use Package\Log\LogManager;

class WriteLogOnSelectLagProblemFoundTest extends Unit
{
    private const QUERY = 'SELECT :id;';
    private const BINDINGS = [':id' => 1];

    protected function _before()
    {
        parent::_before();

        $this->loggerMock = $loggerMock = $this->createMock(Log::class);

        Test::double(LogManager::class, ['getLoggerInstance' => fn () => $loggerMock]);
    }

    protected function _after()
    {
        parent::_after();

        Test::clean(LogManager::class);
    }

    public function testHandle()
    {
        $connectionMock = $this->createMock(MySqlConnection::class);
        $event = new SelectLagProblemFound($connectionMock, [
            'query' => self::QUERY,
            'bindings' => self::BINDINGS,
        ]);
        $listener = new WriteLogOnSelectLagProblemFound();

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->willReturnCallback(function (string $message, array $context) {
                $this->assertArrayHasKey('query', $context);
                $this->assertArrayHasKey('bindings', $context);
                $this->assertArrayHasKey('backtrace', $context);
                $this->assertSame(self::QUERY, $context['query']);
                $this->assertSame(self::BINDINGS, $context['bindings']);
            });

        $listener->handle($event);
    }
}
