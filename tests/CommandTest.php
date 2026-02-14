<?php

namespace Paulobunga\ParkmanSchema\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class CommandTest extends ParkmanTestCase
{
    /** @test */
    public function it_can_run_generate_command()
    {
        $schemaPath = __DIR__ . '/test.prisma';
        File::put($schemaPath, "model User { id Int @id @default(autoincrement()) }");

        Config::set('parkman-schema.paths.migrations', __DIR__ . '/output/migrations');

        Artisan::call('parkman:generate', [
            'schema' => $schemaPath,
            '--migrations' => true,
        ]);

        $this->assertTrue(File::isDirectory(__DIR__ . '/output/migrations'));

        File::delete($schemaPath);
    }
}
