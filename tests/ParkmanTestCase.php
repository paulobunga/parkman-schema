<?php

namespace Paulobunga\ParkmanSchema\Tests;

use Paulobunga\ParkmanSchema\ParkmanSchemaServiceProvider;
use Orchestra\Testbench\TestCase;

class ParkmanTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ParkmanSchemaServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default config
    }

    protected function tearDown(): void
    {
        if (\Illuminate\Support\Facades\File::exists(__DIR__ . '/output')) {
            \Illuminate\Support\Facades\File::deleteDirectory(__DIR__ . '/output');
        }
        parent::tearDown();
    }
}
