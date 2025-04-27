<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Logger\LoggerTrait;
use Package\User\Provider\UserIdProvider;
use Psr\Log\LoggerInterface;
use Throwable;

class WriteLogOnSelectLagProblemFound
{
    use LoggerTrait;

    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(SelectLagProblemFound $event): void
    {
        try {
            /** @var UserIdProvider $idProvider */
            $idProvider = app(UserIdProvider::class);

            $this->logger->error(
                'Replication lag problem found',
                $event->getData() +
                [
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50),
                    'userId' => $idProvider->getUserId(),
                    'recordsModified' => $event->connection->getRecordsModified(),
                ]
            );
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot write log about replication lag problem',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
