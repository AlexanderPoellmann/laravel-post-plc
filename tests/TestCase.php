<?php

namespace AlexanderPoellmann\LaravelPostPlc\Tests;

use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlcServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelPostPlcServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app) {}
}
