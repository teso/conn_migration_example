<?php

declare(strict_types=1);

namespace Package\User\Provider;

use Qless\Jobs\JobData;
use ApiApp;

// For experiment purpose only.
// Do not use it in business logic
class UserIdProvider
{
    public const LOGGER_CHANNEL = 'user_id_provider';

    private int $userId = 0;

    public function __construct(int $userId = 0)
    {
        if ($userId > 0) {
            $this->userId = $userId;
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function applyToJobPayload(JobData $payload): void
    {
        if (!empty($payload['__meta']['userId'])) {
            return;
        }

        $payload['__meta']['userId'] = $this->userId;
    }

    public function initFromJobPayload(JobData $payload): void
    {
        if (empty($payload['__meta']['userId'])) {
            return;
        }

        $this->userId = (int) $payload['__meta']['userId'];
    }

    public function initFromApiApplication(ApiApp $application): void
    {
        $userId = $application->getUserId();

        if (!$userId) {
            return;
        }

        $this->userId = (int) $userId;
    }

    public function reset(): void
    {
        $this->userId = 0;
    }
}
