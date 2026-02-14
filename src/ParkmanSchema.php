<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ParkmanSchema
{
    protected $schema;
    protected $schemaParser;
    protected $stubParser;
    protected $parsedData;

    public function __construct($schema = null, $stubPath = null)
    {
        if ($schema) {
            $this->setSchema($schema);
        }
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

        $migrationContent = $this->generateMigrationContentForModels();
        $this->saveFile('migrations', date('Y_m_d_His') . "_create_tables_from_prisma.php", $migrationContent);
    }

    protected function generateMigrationContentForModels()
    {
        $upMethods = [];
        $downMethods = [];

        foreach ($this->parsedData['models'] as $modelName => $modelData) {
            $tableName = $this->getTableName($modelName);
            $fields = $this->generateFieldsForMigration($modelData);

            $upMethods[] = $this->stubParser->parse('create_table', [
                'table' => $tableName,
                'fields' => $fields
            ]);
            $downMethods[] = $this->stubParser->parse('drop_table', [
                'table' => $tableName
            ]);
        }

        return $this->stubParser->parse('migration', [
            'up_methods' => implode("\n\n        ", $upMethods),
            'down_methods' => implode("\n\n        ", array_reverse($downMethods))
        ]);
    }

    protected function generateFieldsForMigration($modelData)
    {
        $migrationFields = [];
        $hasTimestamps = false;
        $createdAt = false;
        $updatedAt = false;

        foreach ($modelData['fields'] as $field) {
            if ($this->isRelation($field)) continue;

            $name = $field['name'];
            $type = $this->mapPrismaTypeToLaravel($field['type']);

            // Check for ID
            $isId = $this->hasAttribute($field, 'id');
            $isAutoincrement = $this->hasAttribute($field, 'default', 'autoincrement');

            if ($isId && $isAutoincrement && $type === 'integer') {
                $migrationFields[] = "\$table->id('{$name}');";
                continue;
            }

            if ($isId && $isAutoincrement && $type === 'bigInteger') {
                $migrationFields[] = "\$table->id('{$name}');";
                continue;
            }

            // Check for timestamps
            if ($name === 'createdAt' || $name === 'created_at') $createdAt = true;
            if ($name === 'updatedAt' || $name === 'updated_at') $updatedAt = true;

            $nullable = $field['nullable'] ? '->nullable()' : '';
            $unique = $this->hasAttribute($field, 'unique') ? '->unique()' : '';

            $defaultStr = '';
            $defaultAttr = $this->getAttribute($field, 'default');
            if ($defaultAttr && $defaultAttr['params'] !== 'autoincrement') {
                $val = $defaultAttr['params'];
                if ($val === 'now') {
                    $defaultStr = '->useCurrent()';
                } elseif ($val === 'true' || $val === 'false') {
                    $defaultStr = "->default({$val})";
                } elseif (is_numeric($val)) {
                    $defaultStr = "->default({$val})";
                } else {
                    $defaultStr = "->default('{$val}')";
                }
            }

            if ($this->hasAttribute($field, 'updatedAt')) {
                $defaultStr .= '->useCurrentOnUpdate()';
            }

            $migrationFields[] = "\$table->{$type}('{$name}'){$nullable}{$unique}{$defaultStr};";
        }

        // If both createdAt and updatedAt exist, we could use $table->timestamps()
        // but Prisma usually defines them explicitly.

        foreach ($modelData['attributes'] as $attr) {
            if ($attr['name'] === 'unique') {
                $fields = $this->parseAttributeFields($attr['params']);
                $migrationFields[] = "\$table->unique([{$fields}]);";
            }
            if ($attr['name'] === 'index') {
                $fields = $this->parseAttributeFields($attr['params']);
                $migrationFields[] = "\$table->index([{$fields}]);";
            }
            if ($attr['name'] === 'id') {
                $fields = $this->parseAttributeFields($attr['params']);
                $migrationFields[] = "\$table->primary([{$fields}]);";
            }
        }

        return implode("\n            ", $migrationFields);
    }

    protected function parseAttributeFields($params)
    {
        if (preg_match('/\[(.*)\]/', $params, $matches)) {
            return implode(', ', array_map(fn($f) => "'" . trim($f) . "'", explode(',', $matches[1])));
        }
        return "'" . trim($params) . "'";
    }

    public function generateModels()
    {
        foreach ($this->parsedData['models'] as $name => $data) {
            $content = $this->generateModelContent($name, $data);
            $this->saveFile('models', $name . '.php', $content);
        }
    }

    protected function generateModelContent($modelName, $modelData)
    {
        $fillable = [];
        $casts = [];
        $relationships = [];
        $useStatements = [];

        foreach ($modelData['fields'] as $field) {
            if (!$this->isRelation($field)) {
                if (!$this->hasAttribute($field, 'id')) {
                    $fillable[] = "'{$field['name']}'";
                }

                $cast = $this->mapPrismaTypeToLaravelCast($field['type']);
                if ($cast) {
                    $casts[] = "'{$field['name']}' => '{$cast}'";
                }
            } else {
                $relationships[] = $this->generateRelationshipMethod($field);
            }
        }

        return $this->stubParser->parse('model', [
            'namespace' => config('parkman-schema.namespaces.models', 'App\\Models'),
            'use_statements' => implode("\n", array_unique($useStatements)),
            'class_name' => $modelName,
            'fillable' => implode(",\n        ", $fillable),
            'relationships' => implode("\n\n    ", $relationships),
            'casts' => "protected \$casts = [\n        " . implode(",\n        ", $casts) . "\n    ];"
        ]);
    }

    protected function generateRelationshipMethod($field)
    {
        $relatedModel = str_replace('[]', '', $field['type']);
        $methodName = $field['name'];

        if ($field['isList']) {
            $relationType = 'hasMany';
            $returnType = '\Illuminate\Database\Eloquent\Relations\HasMany';
        } else {
            // Check if it's belongsTo (has @relation with fields/references)
            $relationAttr = $this->getAttribute($field, 'relation');
            if ($relationAttr && isset($relationAttr['params']) && str_contains($relationAttr['params'], 'fields')) {
                $relationType = 'belongsTo';
                $returnType = '\Illuminate\Database\Eloquent\Relations\BelongsTo';
            } else {
                $relationType = 'hasOne';
                $returnType = '\Illuminate\Database\Eloquent\Relations\HasOne';
            }
        }

        return "public function {$methodName}(): {$returnType}\n    {\n        return \$this->{$relationType}({$relatedModel}::class);\n    }";
    }

    public function generateControllers()
    {
        foreach ($this->parsedData['models'] as $name => $data) {
            $content = $this->generateControllerContent($name);
            $this->saveFile('controllers', $name . 'Controller.php', $content);
        }
    }

    protected function generateControllerContent($modelName)
    {
        return $this->stubParser->parse('controller', [
            'namespace' => config('parkman-schema.namespaces.controllers', 'App\\Http\\Controllers\\Api'),
            'service_namespace' => config('parkman-schema.namespaces.services', 'App\\Services'),
            'service_name' => $modelName . 'Service',
            'class_name' => $modelName . 'Controller',
        ]);
    }

    public function generateServices()
    {
        foreach ($this->parsedData['models'] as $name => $data) {
            $content = $this->generateServiceContent($name);
            $this->saveFile('services', $name . 'Service.php', $content);
        }
    }

    protected function generateServiceContent($modelName)
    {
        return $this->stubParser->parse('service', [
            'namespace' => config('parkman-schema.namespaces.services', 'App\\Services'),
            'model_namespace' => config('parkman-schema.namespaces.models', 'App\\Models'),
            'model_name' => $modelName,
            'class_name' => $modelName . 'Service',
        ]);
    }

    public function generateFactories()
    {
        foreach ($this->parsedData['models'] as $name => $data) {
            $content = $this->generateFactoryContent($name, $data);
            $this->saveFile('factories', $name . 'Factory.php', $content);
        }
    }

    protected function generateFactoryContent($modelName, $modelData)
    {
        $definitions = [];
        foreach ($modelData['fields'] as $field) {
            if ($this->isRelation($field)) continue;
            if ($this->hasAttribute($field, 'id')) continue;

            $definitions[] = "'{$field['name']}' => " . $this->generateFakerMethod($field);
        }

        return $this->stubParser->parse('factory', [
            'model_namespace' => config('parkman-schema.namespaces.models', 'App\\Models'),
            'model_name' => $modelName,
            'class_name' => $modelName . 'Factory',
            'definition' => implode(",\n            ", $definitions)
        ]);
    }

    protected function generateFakerMethod($field)
    {
        $name = strtolower($field['name']);
        if (str_contains($name, 'email')) return '$this->faker->unique()->safeEmail()';
        if (str_contains($name, 'name')) return '$this->faker->name()';
        if (str_contains($name, 'password')) return "bcrypt('password')";

        switch ($field['type']) {
            case 'String': return '$this->faker->word()';
            case 'Int': return '$this->faker->randomNumber()';
            case 'Float': return '$this->faker->randomFloat()';
            case 'Boolean': return '$this->faker->boolean()';
            case 'DateTime': return '$this->faker->dateTime()';
            case 'Json': return '[]';
            default:
                if (isset($this->parsedData['enums'][$field['type']])) {
                    $values = $this->parsedData['enums'][$field['type']];
                    return '$this->faker->randomElement([' . implode(', ', array_map(fn($v) => "'$v'", $values)) . '])';
                }
                return '$this->faker->word()';
        }
    }

    public function generateSeeders()
    {
        foreach ($this->parsedData['models'] as $name => $data) {
            $content = $this->generateSeederContent($name);
            $this->saveFile('seeders', $name . 'Seeder.php', $content);
        }
    }

    protected function generateSeederContent($modelName)
    {
        return $this->stubParser->parse('seeder', [
            'model_namespace' => config('parkman-schema.namespaces.models', 'App\\Models'),
            'model_name' => $modelName,
            'class_name' => $modelName . 'Seeder',
        ]);
    }

    protected function saveFile($type, $fileName, $content)
    {
        $directory = config("parkman-schema.paths.{$type}");
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        File::put($directory . '/' . $fileName, $content);
    }

    protected function isRelation($field)
    {
        $primitives = ['String', 'Int', 'Float', 'Boolean', 'DateTime', 'Json', 'BigInt', 'Decimal', 'Bytes'];
        $baseType = str_replace('[]', '', $field['type']);
        if (in_array($baseType, $primitives)) return false;
        if (isset($this->parsedData['enums'][$baseType])) return false;
        return true;
    }

    protected function hasAttribute($field, $attrName, $paramValue = null)
    {
        foreach ($field['attributes'] as $attr) {
            if ($attr['name'] === $attrName) {
                if ($paramValue === null) return true;
                return $attr['params'] === $paramValue;
            }
        }
        return false;
    }

    protected function getAttribute($field, $attrName)
    {
        foreach ($field['attributes'] as $attr) {
            if ($attr['name'] === $attrName) return $attr;
        }
        return null;
    }

    protected function mapPrismaTypeToLaravel($prismaType)
    {
        $typeMap = [
            'String' => 'string',
            'Int' => 'integer',
            'Float' => 'float',
            'Boolean' => 'boolean',
            'DateTime' => 'timestamp',
            'BigInt' => 'bigInteger',
            'Decimal' => 'decimal',
            'Json' => 'json',
            'Bytes' => 'binary',
        ];

        if (isset($this->parsedData['enums'][$prismaType])) {
            return 'string'; // Or enum, but string is safer for now if we don't handle enum creation in DB first
        }

        return $typeMap[$prismaType] ?? 'string';
    }

    protected function mapPrismaTypeToLaravelCast($prismaType)
    {
        $castMap = [
            'DateTime' => 'datetime',
            'Boolean' => 'boolean',
            'Json' => 'array',
            'Int' => 'integer',
            'Float' => 'float',
            'Decimal' => 'decimal:2',
        ];

        return $castMap[$prismaType] ?? null;
    }

    protected function getTableName($modelName)
    {
        return Str::plural(Str::snake($modelName));
    }
}
