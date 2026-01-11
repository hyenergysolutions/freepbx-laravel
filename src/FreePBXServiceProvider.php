<?php

declare(strict_types=1);

namespace HyEnergySolutions\FreePBX;

use Illuminate\Support\ServiceProvider;

class FreePBXServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/freepbx.php', 'freepbx');

        $this->app->singleton(FreePBX::class, function ($app) {
            return new FreePBX(
                url: config('freepbx.url'),
                clientId: config('freepbx.client_id'),
                clientSecret: config('freepbx.client_secret'),
            );
        });

        $this->app->alias(FreePBX::class, 'freepbx');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/freepbx.php' => config_path('freepbx.php'),
            ], 'freepbx-config');
        }
    }
}
