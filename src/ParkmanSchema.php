<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ParkmanSchema
{
    protected $schema;

    public function __construct($schema = null)
    {
        $this->schema = $schema;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    public function generateModels()
    {
        if (!$this->schema) {
            throw new \Exception('Prisma schema not set');
        }

        $models = $this->parseSchema();

        foreach ($models as $model) {
            $this->createModel($model);
        }
    }

    public function generateMigrations()
    {
        if (!$this->schema) {
            throw new \Exception('Prisma schema not set');
        }

        $models = $this->parseSchema();

        foreach ($models as $model) {
            $this->createMigration($model);
        }
    }

    protected function parseSchema()
    {
        // Implement schema parsing logic here
        // This is a placeholder and should be replaced with actual parsing logic
        $models = [];
        // Parse $this->schema and populate $models
        return $models;
    }

    protected function createModel($modelData)
    {
        $modelName = $modelData['name'];
        $modelContent = $this->generateModelContent($modelData);

        $path = app_path("Models/{$modelName}.php");
        file_put_contents($path, $modelContent);
    }

    protected function createMigration($modelData)
    {
        $tableName = Str::plural(Str::snake($modelData['name']));
        $migrationName = "create_{$tableName}_table";

        Artisan::call('make:migration', [
            'name' => $migrationName,
        ]);

        // Update the created migration file with schema information
        $migrationFile = $this->getLatestMigrationFile();
        $migrationContent = $this->generateMigrationContent($modelData);

        file_put_contents($migrationFile, $migrationContent);
    }

    protected function generateModelContent($modelData)
    {
        // Implement model content generation logic
        // This is a placeholder and should be replaced with actual generation logic
        return "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass {$modelData['name']} extends Model\n{\n    // Model implementation\n}";
    }

    protected function generateMigrationContent($modelData)
    {
        // Implement migration content generation logic
        // This is a placeholder and should be replaced with actual generation logic
        return "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nreturn new class extends Migration\n{\n    public function up()\n    {\n        // Migration implementation\n    }\n\n    public function down()\n    {\n        // Rollback implementation\n    }\n};";
    }

    protected function getLatestMigrationFile()
    {
        $migrationPath = database_path('migrations');
        $files = glob($migrationPath . '/*.php');
        return end($files);
    }


}
