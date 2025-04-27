<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Package\Api\ApiV3\Events\FinishEvent;
use Package\Database\DatabaseUseStatistics;
use Package\Database\Logger\LoggerTrait;
use Package\MetricsService\Interfaces\StatsdInterface;
use Package\MetricsService\Metrics\Database;
use Package\MetricsService\StatsdFactory;
use ApiApp;
use Throwable;

class SendMetricsOnApiFinishEvent
{
    use LoggerTrait;

    private StatsdInterface $statsd;

    public function __construct()
    {
        $this->statsd = StatsdFactory::createStatsd(StatsdFactory::PROMETHEUS);
        $this->logger = $this->getLogger();
    }

    public function handle(FinishEvent $event): void
    {
        try {
            $fallbackCounter = DatabaseUseStatistics::getInstance()->getSelectFallbackUseCounter();
            $lagProblemCounter = DatabaseUseStatistics::getInstance()->getSelectLagProblemCounter();
            $readCounter = DatabaseUseStatistics::getInstance()->getReadEndpointCounter();
            $writeCounter = DatabaseUseStatistics::getInstance()->getWriteEndpointCounter();

            // Skip routes without requests to database
            // or not-in-experiment cases
            if (!$readCounter && !$writeCounter) {
                return;
            }

            $route = $this->getRoute();

            $this->statsd->updateStats(
                Database::SELECT_FALLBACK_USE,
                $fallbackCounter,
                1.0,
                [
                    'route' => $route,
                    'method' => $_SERVER['REQUEST_METHOD'],
                ]
            );

            $this->statsd->updateStats(
                Database::SELECT_LAG_PROBLEM,
                $lagProblemCounter,
                1.0,
                [
                    'route' => $route,
                    'method' => $_SERVER['REQUEST_METHOD'],
                ]
            );

            $this->statsd->updateStats(
                Database::SELECT_READ_ENDPOINT_WEB,
                $readCounter,
                1.0,
                [
                    'route' => $route,
                    'method' => $_SERVER['REQUEST_METHOD'],
                ]
            );

            $this->statsd->updateStats(
                Database::SELECT_WRITE_ENDPOINT_WEB,
                $writeCounter,
                1.0,
                [
                    'route' => $route,
                    'method' => $_SERVER['REQUEST_METHOD'],
                ]
            );
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot send API metrics',
                [
                    'error' => $t,
                ]
            );
        }
    }

    private function getRoute(): string
    {
        $application = ApiApp::getInstance();
        $module = $application->router->op;
        $action = $application->router->action ?? 'index';

        return '/' . $module . '/' . $action;
    }
}
