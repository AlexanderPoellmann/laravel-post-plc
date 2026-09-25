<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Tests;

use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlcServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RicorocksDigitalAgency\Soap\Providers\SoapServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            SoapServiceProvider::class,
            LaravelPostPlcServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('post-plc.client_id', '123456');
        $app['config']->set('post-plc.org_unit_id', '654321');
        $app['config']->set('post-plc.org_unit_guid', '00000000-0000-0000-0000-000000000001');
        $app['config']->set('post-plc.identifier', 'PLC Tests');
        $app['config']->set('post-plc.sandbox', true);
    }
}
