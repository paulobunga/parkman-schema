<?php

namespace Paulobunga\ParkmanSchema\Commands;

use Illuminate\Console\Command;
use Paulobunga\ParkmanSchema\ParkmanSchema;

class ParkmanGenerateCommand extends Command
{
    protected $signature = 'parkman:generate {schema : Path to the Prisma schema file}
                            {--models : Generate models}
                            {--controllers : Generate API controllers}
                            {--factories : Generate factories}
                            {--seeders : Generate seeders}
                            {--services : Generate services}
                            {--migrations : Generate migrations}
                            {--all : Generate everything}';

    protected $description = 'Generate Laravel components based on Prisma schema';

    public function handle()
    {
        $schemaPath = $this->argument('schema');

        if (!file_exists($schemaPath)) {
            $this->error("Schema file not found: {$schemaPath}");
            return 1;
        }

        $schema = file_get_contents($schemaPath);
        $parkman = new ParkmanSchema($schema);

        $generateAll = $this->option('all');

        // Migrations are first class citizens, generate by default or if requested
        if ($generateAll || $this->option('migrations') || (!$this->anyOptionSet())) {
            $this->info('Generating migrations...');
            $parkman->generateMigrations();
        }

        if ($generateAll || $this->option('models')) {
            $this->info('Generating models...');
            $parkman->generateModels();
        }

        if ($generateAll || $this->option('controllers')) {
            $this->info('Generating controllers...');
            $parkman->generateControllers();
        }

        if ($generateAll || $this->option('services')) {
            $this->info('Generating services...');
            $parkman->generateServices();
        }

        if ($generateAll || $this->option('factories')) {
            $this->info('Generating factories...');
            $parkman->generateFactories();
        }

        if ($generateAll || $this->option('seeders')) {
            $this->info('Generating seeders...');
            $parkman->generateSeeders();
        }

        $this->info('Generation completed successfully.');
    }

    protected function anyOptionSet()
    {
        return $this->option('models') ||
               $this->option('controllers') ||
               $this->option('factories') ||
               $this->option('seeders') ||
               $this->option('services') ||
               $this->option('migrations');
    }
}
