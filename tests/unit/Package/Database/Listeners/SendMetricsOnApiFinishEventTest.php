<?php

namespace unit\Package\Database\Listeners;

use AspectMock\Test;
use Codeception\Stub;
use Codeception\Test\Unit;
use Package\Api\ApiV3\Events\FinishEvent;
use Package\Database\DatabaseUseStatistics;
use Package\Database\Listeners\SendMetricsOnApiFinishEvent;
use Package\MetricsService\Interfaces\StatsdInterface;
use Package\MetricsService\Metrics\Database;
use Package\MetricsService\StatsdFactory;
use ApiRouter;
use ApiApp;

class SendMetricsOnApiFinishEventTest extends Unit
{
    private const MODULE = 'module';
    private const ACTION = 'action';
    private const METHOD = 'GET';
    private const FALLBACK_COUNTER = 6;
    private const LAG_PROBLEM_COUNTER = 3;
    private const READ_COUNTER = 2;
    private const WRITE_COUNTER = 5;

    private StatsdInterface $statsdMock;

    protected function _before()
    {
        parent::_before();

        $this->statsdMock = $statsdMock = $this->createMock(StatsdInterface::class);

        $routerStub = Stub::make(ApiRouter::class, ['op' => self::MODULE, 'action' => self::ACTION]);
        $applicationStub = Stub::make(ApiApp::class, ['router' => $routerStub]);

        Test::double(StatsdFactory::class, ['createStatsd' => fn () => $statsdMock]);
        Test::double(ApiApp::class, ['getInstance' => fn () => $applicationStub]);

        $_SERVER['REQUEST_METHOD'] = self::METHOD;
    }

    protected function _after()
    {
        parent::_after();

        Test::clean(StatsdFactory::class);
        Test::clean(ApiApp::class);

        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testHandleWhenCountersAreEqualZero()
    {
        $eventMock = $this->createMock(FinishEvent::class);
        $listener = new SendMetricsOnApiFinishEvent();

        $this->statsdMock
            ->expects($this->never())
            ->method('updateStats');

        $listener->handle($eventMock);
    }

    public function testHandleWhenCountersAreGreaterThanZero()
    {
        $this->setUpCounters();

        $eventMock = $this->createMock(FinishEvent::class);
        $listener = new SendMetricsOnApiFinishEvent();

        $expectedArguments = [
            [
                Database::SELECT_FALLBACK_USE,
                self::FALLBACK_COUNTER,
                1.0,
                [
                    'route' => $this->getRoute(),
                    'method' => self::METHOD,
                ]
            ],
            [
                Database::SELECT_LAG_PROBLEM,
                self::LAG_PROBLEM_COUNTER,
                1.0,
                [
                    'route' => $this->getRoute(),
                    'method' => self::METHOD,
                ]
            ],
            [
                Database::SELECT_READ_ENDPOINT_WEB,
                self::READ_COUNTER,
                1.0,
                [
                    'route' => $this->getRoute(),
                    'method' => self::METHOD,
                ]
            ],
            [
                Database::SELECT_WRITE_ENDPOINT_WEB,
                self::WRITE_COUNTER,
                1.0,
                [
                    'route' => $this->getRoute(),
                    'method' => self::METHOD,
                ]
            ],
        ];

        $this->statsdMock
            ->expects($this->exactly(4))
            ->method('updateStats')
            ->willReturnCallback(function () use (&$expectedArguments) {
                $this->assertSame(array_shift($expectedArguments), func_get_args());
            });

        $listener->handle($eventMock);

        $this->tearDownCounters();
    }

    private function setUpCounters(): void
    {
        for ($i = 0; $i < self::FALLBACK_COUNTER; $i++) {
            DatabaseUseStatistics::getInstance()->incrementSelectFallbackUseCounter();
        }

        for ($i = 0; $i < self::LAG_PROBLEM_COUNTER; $i++) {
            DatabaseUseStatistics::getInstance()->incrementSelectLagProblemCounter();
        }

        for ($i = 0; $i < self::READ_COUNTER; $i++) {
            DatabaseUseStatistics::getInstance()->incrementReadEndpointCounter();
        }

        for ($i = 0; $i < self::WRITE_COUNTER; $i++) {
            DatabaseUseStatistics::getInstance()->incrementWriteEndpointCounter();
        }
    }

    private function tearDownCounters(): void
    {
        DatabaseUseStatistics::getInstance()->reset();
    }

    private function getRoute(): string
    {
        return '/' . self::MODULE . '/' . self::ACTION;
    }
}
