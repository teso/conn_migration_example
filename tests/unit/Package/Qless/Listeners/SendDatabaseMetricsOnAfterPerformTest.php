<?php

namespace unit\Package\Qless\Listeners;

use AspectMock\Test;
use Codeception\Test\Unit;
use Package\Database\DatabaseUseStatistics;
use Package\MetricsService\Interfaces\StatsdInterface;
use Package\MetricsService\Metrics\Database;
use Package\MetricsService\StatsdFactory;
use Package\Qless\Listeners\SendDatabaseMetricsOnAfterPerform;
use Qless\Client;
use Qless\Events\User\Job\AfterPerform;
use Qless\Jobs\BaseJob;

class SendDatabaseMetricsOnAfterPerformTest extends Unit
{
    private const JOB_CLASS = '\Example\Job';
    private const FALLBACK_COUNTER = 6;
    private const LAG_PROBLEM_COUNTER = 3;
    private const READ_COUNTER = 2;
    private const WRITE_COUNTER = 5;

    private StatsdInterface $statsdMock;

    protected function _before()
    {
        parent::_before();

        $this->statsdMock = $statsdMock = $this->createMock(StatsdInterface::class);

        Test::double(StatsdFactory::class, ['createStatsd' => fn () => $statsdMock]);
    }

    protected function _after()
    {
        parent::_after();

        Test::clean(StatsdFactory::class);
    }

    public function testHandlerWhenCountersAreEqualZero()
    {
        $eventMock = $this->createMock(AfterPerform::class);
        $listener = new SendDatabaseMetricsOnAfterPerform();

        $this->statsdMock
            ->expects($this->never())
            ->method('updateStats');

        $listener->handler($eventMock);
    }

    public function testHandlerWhenCountersAreGreaterThanZero()
    {
        $this->setUpCounters();

        $clientMock = $this->createMock(Client::class);
        $job = new BaseJob($clientMock, [
            'jid' => 0,
            'klass' => self::JOB_CLASS,
            'queue' => '',
            'worker' => '',
            'state' => '',
            'data' => '',
        ]);
        $event = new AfterPerform(null, $job);
        $listener = new SendDatabaseMetricsOnAfterPerform();
        $expectedArguments = [
            [
                Database::SELECT_FALLBACK_USE,
                self::FALLBACK_COUNTER,
                1.0,
                [
                    'job_name' => self::JOB_CLASS,
                ]
            ],
            [
                Database::SELECT_LAG_PROBLEM,
                self::LAG_PROBLEM_COUNTER,
                1.0,
                [
                    'job_name' => self::JOB_CLASS,
                ]
            ],
            [
                Database::SELECT_READ_ENDPOINT_QLESS,
                self::READ_COUNTER,
                1.0,
                [
                    'job_name' => self::JOB_CLASS,
                ]
            ],
            [
                Database::SELECT_WRITE_ENDPOINT_QLESS,
                self::WRITE_COUNTER,
                1.0,
                [
                    'job_name' => self::JOB_CLASS,
                ]
            ],
        ];

        $this->statsdMock
            ->expects($this->exactly(4))
            ->method('updateStats')
            ->willReturnCallback(function () use (&$expectedArguments) {
                $this->assertSame(array_shift($expectedArguments), func_get_args());
            });

        $listener->handler($event);

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
}
