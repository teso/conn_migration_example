<?php

namespace package\Qless\Listeners;

use Package\Qless\Listeners\Contracts\JobAfterPerformListener;
use Package\User\Provider\UserIdProvider;
use Qless\Events\User\Job\AfterPerform;
use Throwable;

class ResetUserIdProviderOnAfterPerform implements JobAfterPerformListener
{
    public function handler(AfterPerform $event): void
    {
        try {
            /** @var UserIdProvider $idProvider */
            $idProvider = app(UserIdProvider::class);
            $idProvider->reset();
        } catch (Throwable $t) {
            logger(UserIdProvider::LOGGER_CHANNEL)->error(
                'Cannot reset user id provider after job complete',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
