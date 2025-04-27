<?php

namespace Package\Providers;

use Package\Database\Events\ReadEndpointUsed;
use Package\Database\Events\SelectFallbackUsed;
use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Events\WriteEndpointUsed;
use Package\Database\Listeners\AddTraceSpanOnQueryExecuted;
use Package\Database\Listeners\ChangeConnectionOnEloquentModelBooted;
use Package\Database\Listeners\IncrementCounterOnReadEndpointUsed;
use Package\Database\Listeners\IncrementCounterOnSelectFallbackUsed;
use Package\Database\Listeners\IncrementCounterOnSelectLagProblemFound;
use Package\Database\Listeners\IncrementCounterOnWriteEndpointUsed;
use Package\Database\Listeners\WriteLogOnSelectLagProblemFound;

class EventServiceProvider
{
    protected Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    protected $listeners = [
        QueryExecuted::class => [
            AddTraceSpanOnQueryExecuted::class,
        ],

        'eloquent.booted: *' => [
            ChangeConnectionOnEloquentModelBooted::class,
        ],

        SelectFallbackUsed::class => [
            IncrementCounterOnSelectFallbackUsed::class,
        ],

        SelectLagProblemFound::class => [
            IncrementCounterOnSelectLagProblemFound::class,
            WriteLogOnSelectLagProblemFound::class,
        ],

        ReadEndpointUsed::class => [
            IncrementCounterOnReadEndpointUsed::class,
        ],

        WriteEndpointUsed::class => [
            IncrementCounterOnWriteEndpointUsed::class,
        ],
    ];

    public function boot(): void
    {
        foreach ($this->listeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->dispatcher->listen(array($event), $listener);
            }
        }

        foreach ($this->subscribers as $subscriber) {
            $this->dispatcher->subscribe($subscriber);
        }
    }
}
