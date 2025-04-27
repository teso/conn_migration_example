<?php

namespace Package\User\Application\Providers;

use Illuminate\Container\Container;
use Package\User\Provider\UserIdProvider;

class ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(UserIdProvider::class);
        $container
            ->when(UserIdProvider::class)
            ->needs('$userId')
            ->give(fn() => Auth::userId());
    }
}
