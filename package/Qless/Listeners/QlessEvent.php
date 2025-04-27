<?php

namespace Package\Qless\Listeners;

use Package\Cannon\Jobs\Listeners\CannonJobFailListener;
use Package\Cannon\Jobs\Listeners\CannonJobFinishListener;
use Package\Qless\Listeners\Contracts\JobBeforePerformListener;

class QlessEvent
{
    private $listeners = [
        'queue:beforeEnqueue' => [
            OpenTracingBeforeListener::class,
            OpenSpanAndAddInterserviceHeadersOnBeforeEnqueue::class,
            AddUserIdToPayloadOnBeforeEnqueue::class,
        ],
        'queue:afterEnqueue' => [
            OpenTracingAfterListener::class,
        ],
        'job:beforePerform' => [
            OpenTracingBeforePerformListener::class,
            InitInterserviceHeadersOnBeforePerform::class,
            InitOpenTracingOnBeforePerform::class,
            ListenMetricsBeforeJobStarts::class,
            InitUserIdProviderOnBeforePerform::class,
            ResetDatabaseUseStatisticsOnBeforePerform::class,
        ],
        'job:afterPerform' => [
            ListenMetricsAfterJobEnds::class,
            OpenTracingFinishListener::class,
            OpenTracingFinishOnAfterPerform::class,
            CannonJobFinishListener::class,
            ResetDataModificationStateOnAfterPerform::class,
            ResetUserIdProviderOnAfterPerform::class,
            SendDatabaseMetricsOnAfterPerform::class,
        ],
        'job:onFailure' => [
            OpenTracingFailureListener::class,
            OpenTracingFinishOnFailure::class,
            CannonJobFailListener::class,
        ],
    ];

    public function fire(string $eventName, $event): void
    {
        $listeners = $this->listeners[$eventName] ?? [];
        foreach ($listeners as $listenerClass) {
            try {
                /** @var JobBeforePerformListener $listener */
                $listener = new $listenerClass;
                $listener->handler($event);
            } catch (\Throwable $e) {
                // Log and skip
                logger('qless')->error($e->getMessage(), [
                    'listenerClass' => $listenerClass,
                ]);
            }
        }
    }
}
