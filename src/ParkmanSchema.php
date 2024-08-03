<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ParkmanSchema
{
    protected $schema;
    protected $schemaParser;
    protected $stubParser;
    protected $parsedData;

    public function __construct($schema = null, $stubPath = null)
    {
        $this->setSchema($schema);
        $this->stubParser = new StubParser($stubPath ?? __DIR__ . '/../stubs');
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
        $this->schemaParser = new PrismaSchemaParser($schema);
        $this->parsedData = $this->schemaParser->parse();
        return $this;
    }

    public function generateMigrations()
    {
        if (!$this->schema) {
            throw new \Exception('Prisma schema not set');
        }

        $this->createMigrationForModels();
        $this->createMigrationForOperations();
    }

    protected function createMigrationForModels()
    {
        $migrationContent = $this->generateMigrationContentForModels();
        $this->createMigrationFile('create_tables', $migrationContent);
    }

    protected function createMigrationForOperations()
    {
        $migrationContent = $this->generateMigrationContentForOperations();
        $this->createMigrationFile('alter_tables', $migrationContent);
    }

    protected function createMigrationFile($name, $content)
    {
        $fileName = date('Y_m_d_His') . "_" . $name . ".php";
        $path = database_path("migrations/{$fileName}");
        file_put_contents($path, $content);
    }

    protected function generateMigrationContentForModels()
    {
        $upMethods = [];
        $downMethods = [];

        foreach ($this->parsedData['models'] as $modelName => $modelData) {
            $tableName = $this->getTableName($modelName);
            $fields = $this->generateFieldsForMigration($modelData['fields']);

            $upMethods[] = $this->generateCreateTableMethod($tableName, $fields);
            $downMethods[] = $this->generateDropTableMethod($tableName);
        }

        return $this->stubParser->parse('migration', [
            'up_methods' => implode("\n\n", $upMethods),
            'down_methods' => implode("\n\n", array_reverse($downMethods))
        ]);
    }

    protected function generateMigrationContentForOperations()
    {
        $upMethods = [];
        $downMethods = [];

        foreach ($this->parsedData['operations'] as $operation) {
            switch ($operation['type']) {
                case 'AlterTable':
                    list($up, $down) = $this->handleAlterTable($operation);
                    break;
                case 'RenameTable':
                    list($up, $down) = $this->handleRenameTable($operation);
                    break;
                case 'AddForeignKey':
                    list($up, $down) = $this->handleAddForeignKey($operation);
                    break;
                case 'DropForeignKey':
                    list($up, $down) = $this->handleDropForeignKey($operation);
                    break;
                default:
                    continue 2;
            }
            $upMethods[] = $up;
            $downMethods[] = $down;
        }

        return $this->stubParser->parse('migration', [
            'up_methods' => implode("\n\n", $upMethods),
            'down_methods' => implode("\n\n", array_reverse($downMethods))
        ]);
    }

    protected function handleAlterTable($operation)
    {
        // Implementation remains the same
    }

    protected function handleRenameTable($operation)
    {
        // Implementation remains the same
    }

    protected function handleAddForeignKey($operation)
    {
        // Implementation remains the same
    }

    protected function handleDropForeignKey($operation)
    {
        // Implementation remains the same
    }

    protected function generateCreateTableMethod($tableName, $fields)
    {
        return $this->stubParser->parse('create_table', [
            'table' => $tableName,
            'fields' => $fields
        ]);
    }

    protected function generateDropTableMethod($tableName)
    {
        return $this->stubParser->parse('drop_table', [
            'table' => $tableName
        ]);
    }

    protected function generateFieldsForMigration($fields)
    {
        $migrationFields = [];
        foreach ($fields as $field) {
            $type = $this->mapPrismaTypeToLaravel($field['type']);
            $nullable = $field['nullable'] ? '->nullable()' : '';
            $migrationFields[] = "\$table->{$type}('{$field['name']}'){$nullable};";
        }
        return implode("\n            ", $migrationFields);
    }

    protected function mapPrismaTypeToLaravel($prismaType)
    {
        $typeMap = [
            'String' => 'string',
            'Int' => 'integer',
            'Float' => 'float',
            'Boolean' => 'boolean',
            'DateTime' => 'timestamp',
            // Add more type mappings as needed
            'BigInt' => 'bigInteger',
            'SmallInt' => 'smallInteger',
            'Long' => 'bigInteger',
            'Double' => 'double',
            'Decimal' => 'decimal',
            'Text' => 'text',
            'Json' => 'json',
        ];

        return $typeMap[$prismaType] ?? 'string';
    }

    protected function getTableName($modelName)
    {
        return Str::plural(Str::snake($modelName));
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
