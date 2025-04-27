<?php

namespace package\Qless\Listeners;

use Package\Qless\Listeners\Contracts\JobBeforePerformListener;
use Package\User\Provider\UserIdProvider;
use Qless\Events\User\Job\BeforePerform;
use Throwable;

class InitUserIdProviderOnBeforePerform implements JobBeforePerformListener
{
    public function handler(BeforePerform $event): void
    {
        try {
            $payload = $event->getJob()->getData();

            /** @var UserIdProvider $idProvider */
            $idProvider = app(UserIdProvider::class);
            $idProvider->initFromJobPayload($payload);
        } catch (Throwable $t) {
            logger(UserIdProvider::LOGGER_CHANNEL)->error(
                'Cannot init user id provider before job start',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
