<?php

namespace Paulobunga\ParkmanSchema\Tests;

use Paulobunga\ParkmanSchema\ParkmanSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class GenerationTest extends ParkmanTestCase
{
    /** @test */
    public function it_can_generate_models()
    {
        $schema = "model User { id Int @id @default(autoincrement()) \n email String @unique }";
        $parkman = new ParkmanSchema($schema);

        // Mock paths
        Config::set('parkman-schema.paths.models', __DIR__ . '/output/Models');
        Config::set('parkman-schema.namespaces.models', 'App\\Models');

        if (File::exists(__DIR__ . '/output')) {
            File::deleteDirectory(__DIR__ . '/output');
        }

        $parkman->generateModels();

        $this->assertTrue(File::exists(__DIR__ . '/output/Models/User.php'));
        $content = File::get(__DIR__ . '/output/Models/User.php');
        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString("'email'", $content);
    }

    /** @test */
    public function it_can_generate_migrations()
    {
        $schema = "model User { id Int @id @default(autoincrement()) \n email String @unique }";
        $parkman = new ParkmanSchema($schema);

        Config::set('parkman-schema.paths.migrations', __DIR__ . '/output/migrations');

        $parkman->generateMigrations();

        $files = File::files(__DIR__ . '/output/migrations');
        $this->assertCount(1, $files);
        $content = File::get($files[0]);
        $this->assertStringContainsString("Schema::create('users'", $content);
        $this->assertStringContainsString("\$table->id('id')", $content);
        $this->assertStringContainsString("\$table->string('email')->unique()", $content);
    }

    /** @test */
    public function it_can_generate_all_components()
    {
        $schema = "model User { id Int @id @default(autoincrement()) \n email String @unique }";
        $parkman = new ParkmanSchema($schema);

        Config::set('parkman-schema.paths.models', __DIR__ . '/output/Models');
        Config::set('parkman-schema.paths.controllers', __DIR__ . '/output/Controllers');
        Config::set('parkman-schema.paths.services', __DIR__ . '/output/Services');
        Config::set('parkman-schema.paths.factories', __DIR__ . '/output/factories');
        Config::set('parkman-schema.paths.seeders', __DIR__ . '/output/seeders');

        $parkman->generateModels();
        $parkman->generateControllers();
        $parkman->generateServices();
        $parkman->generateFactories();
        $parkman->generateSeeders();

        $this->assertTrue(File::exists(__DIR__ . '/output/Models/User.php'));
        $this->assertTrue(File::exists(__DIR__ . '/output/Controllers/UserController.php'));
        $this->assertTrue(File::exists(__DIR__ . '/output/Services/UserService.php'));
        $this->assertTrue(File::exists(__DIR__ . '/output/factories/UserFactory.php'));
        $this->assertTrue(File::exists(__DIR__ . '/output/seeders/UserSeeder.php'));
    }
}
