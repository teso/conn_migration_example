<?php

namespace Package\Qless\Listeners;

use Package\Qless\Listeners\Contracts\QueueBeforeListener;
use Package\User\Provider\UserIdProvider;
use Qless\Events\User\Queue\BeforeEnqueue;
use Throwable;

class AddUserIdToPayloadOnBeforeEnqueue implements QueueBeforeListener
{
    public function handler(BeforeEnqueue $event): void
    {
        try {
            $payload = $event->getData();

            /** @var UserIdProvider $idProvider */
            $idProvider = app(UserIdProvider::class);
            $idProvider->applyToJobPayload($payload);
        } catch (Throwable $t) {
            logger(UserIdProvider::LOGGER_CHANNEL)->error(
                'Cannot add used id to payload before job enqueue',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
