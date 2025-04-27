<?php

namespace Package\Qless\Listeners;

use Package\Database\DatabaseUseStatistics;
use Package\Database\Logger\LoggerTrait;
use Package\MetricsService\Interfaces\StatsdInterface;
use Package\MetricsService\Metrics\Database;
use Package\MetricsService\StatsdFactory;
use Package\Qless\Listeners\Contracts\JobAfterPerformListener;
use Qless\Events\User\Job\AfterPerform;
use Throwable;

class SendDatabaseMetricsOnAfterPerform implements JobAfterPerformListener
{
    use LoggerTrait;

    private StatsdInterface $statsd;

    public function __construct()
    {
        $this->statsd = StatsdFactory::createStatsd(StatsdFactory::PROMETHEUS);
        $this->logger = $this->getLogger();
    }

    public function handler(AfterPerform $event): void
    {
        try {
            $fallbackCounter = DatabaseUseStatistics::getInstance()->getSelectFallbackUseCounter();
            $lagProblemCounter = DatabaseUseStatistics::getInstance()->getSelectLagProblemCounter();
            $readCounter = DatabaseUseStatistics::getInstance()->getReadEndpointCounter();
            $writeCounter = DatabaseUseStatistics::getInstance()->getWriteEndpointCounter();

            // Skip jobs without requests to database
            // or not-in-experiment cases
            if (!$readCounter && !$writeCounter) {
                return;
            }

            $this->statsd->updateStats(
                Database::SELECT_FALLBACK_USE,
                $fallbackCounter,
                1.0,
                [
                    'job_name' => $event->getJob()->klass,
                ]
            );

            $this->statsd->updateStats(
                Database::SELECT_LAG_PROBLEM,
                $lagProblemCounter,
                1.0,
                [
                    'job_name' => $event->getJob()->klass,
                ]
            );

            $this->statsd->updateStats(
                Database::SELECT_READ_ENDPOINT_QLESS,
                $readCounter,
                1.0,
                [
                    'job_name' => $event->getJob()->klass,
                ]
            );

            $this->statsd->updateStats(
                Database::SELECT_WRITE_ENDPOINT_QLESS,
                $writeCounter,
                1.0,
                [
                    'job_name' => $event->getJob()->klass,
                ]
            );
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot send database metrics after job complete',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
