<?php

namespace HyEnergySolutions\FreePBX\Tests;

use HyEnergySolutions\FreePBX\FreePBXServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FreePBXServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('freepbx.url', 'http://test-pbx.local');
        $app['config']->set('freepbx.client_id', 'test-client-id');
        $app['config']->set('freepbx.client_secret', 'test-client-secret');
    }
}
