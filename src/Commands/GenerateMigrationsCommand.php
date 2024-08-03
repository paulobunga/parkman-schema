<?php

namespace Paulobunga\ParkmanSchema\Commands;

use Illuminate\Console\Command;
use Paulobunga\ParkmanSchema\ParkmanSchema;

class GenerateMigrationsCommand extends Command
{
    protected $signature = 'parkman:generate-migrations {schema : Path to the Prisma schema file}';
    protected $description = 'Generate Laravel migrations based on Prisma schema';

    public function handle()
    {
        $schemaPath = $this->argument('schema');

        if (!file_exists($schemaPath)) {
            $this->error("Schema file not found: {$schemaPath}");
            return 1;
        }

        $schema = file_get_contents($schemaPath);

        $parkman = new ParkmanSchema($schema);
        $parkman->generateMigrations();

        $this->info('Migrations generated successfully.');
    }
}