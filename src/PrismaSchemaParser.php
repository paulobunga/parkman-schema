<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Str;

class PrismaSchemaParser
{
    protected $schema;
    protected $models = [];
    protected $enums = [];
    protected $operations = [];

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function parse()
    {
        $this->parseEnums();
        $this->parseModels();
        // Operations are usually for diffs/migrations, keeping for compatibility if needed
        // but Prisma schema is usually declarative.

        $this->reorderModels();

        return [
            'models' => $this->models,
            'enums' => $this->enums,
            'operations' => $this->operations
        ];
    }

    protected function parseEnums()
    {
        preg_match_all('/enum\s+(\w+)\s*{([\s\S]*?)}/', $this->schema, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $enumName = $match[1];
            $valuesContent = $match[2];
            $values = [];
            foreach (explode("\n", $valuesContent) as $line) {
                $line = trim($line);
                if ($line && !str_starts_with($line, '//')) {
                    // Enums can have @map
                    if (preg_match('/(\w+)/', $line, $valMatch)) {
                        $values[] = $valMatch[1];
                    }
                }
            }
            $this->enums[$enumName] = $values;
        }
    }

    protected function parseModels()
    {
        preg_match_all('/model\s+(\w+)\s*{([\s\S]*?)}/', $this->schema, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $modelName = $match[1];
            $content = $match[2];
            $this->models[$modelName] = $this->parseModelContent($content);
        }
    }

    protected function parseModelContent($content)
    {
        $fields = [];
        $modelAttributes = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || str_starts_with($line, '//')) continue;

            if (str_starts_with($line, '@@')) {
                // Model level attribute
                if (preg_match('/@@(\w+)(?:\s*(\((?:[^()]++|(?2))*\)))?/', $line, $attrMatch)) {
                    $modelAttributes[] = [
                        'name' => $attrMatch[1],
                        'params' => isset($attrMatch[2]) ? trim($attrMatch[2], '()') : null
                    ];
                }
            } else {
                // Field
                // Regex for field: name type attributes
                // Example: id Int @id @default(autoincrement())
                // Example: email String? @unique
                // Example: posts Post[]
                if (preg_match('/^(\w+)\s+([\w\[\]]+)(\??)(\s+.*)?$/', $line, $fieldMatch)) {
                    $name = $fieldMatch[1];
                    $type = $fieldMatch[2];
                    $nullable = $fieldMatch[3] === '?';
                    $attributesRaw = isset($fieldMatch[4]) ? trim($fieldMatch[4]) : '';

                    $attributes = $this->parseFieldAttributes($attributesRaw);

                    $fields[] = [
                        'name' => $name,
                        'type' => $type,
                        'nullable' => $nullable,
                        'attributes' => $attributes,
                        'isList' => str_ends_with($type, '[]')
                    ];
                }
            }
        }

        return [
            'fields' => $fields,
            'attributes' => $modelAttributes,
            'relations' => array_filter($fields, function($f) {
                return $this->isRelation($f);
            })
        ];
    }

    protected function parseFieldAttributes($attributesRaw)
    {
        $attributes = [];
        if (!$attributesRaw) return $attributes;

        // Matches @attr or @attr(params) with support for nested parentheses
        preg_match_all('/@(\w+)(?:\s*(\((?:[^()]++|(?2))*\)))?/', $attributesRaw, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $params = isset($match[2]) ? trim($match[2], '()') : null;
            $attributes[] = [
                'name' => $match[1],
                'params' => $params
            ];
        }
        return $attributes;
    }

    protected function isRelation($field)
    {
        // A field is a relation if its type is not a primitive type or an enum
        $primitives = ['String', 'Int', 'Float', 'Boolean', 'DateTime', 'Json', 'BigInt', 'Decimal', 'Bytes'];
        $baseType = str_replace('[]', '', $field['type']);

        if (in_array($baseType, $primitives)) return false;
        if (isset($this->enums[$baseType])) return false;

        return true;
    }

    protected function reorderModels()
    {
        $orderedModels = [];
        $unorderedModels = $this->models;

        $maxIterations = count($unorderedModels) * 2;
        $iterations = 0;

        while (!empty($unorderedModels) && $iterations < $maxIterations) {
            $iterations++;
            $addedModels = [];

            foreach ($unorderedModels as $modelName => $modelData) {
                $canAdd = true;

                foreach ($modelData['relations'] as $relation) {
                    $relatedModel = str_replace('[]', '', $relation['type']);
                    // If it's a many-to-many or the other side of a relation, it might not be a dependency for migration creation
                    // but for now let's keep it simple.
                    if (isset($unorderedModels[$relatedModel]) && $relatedModel !== $modelName) {
                        $canAdd = false;
                        break;
                    }
                }

                if ($canAdd) {
                    $orderedModels[$modelName] = $modelData;
                    $addedModels[] = $modelName;
                }
            }

            foreach ($addedModels as $modelName) {
                unset($unorderedModels[$modelName]);
            }

            if (empty($addedModels) && !empty($unorderedModels)) {
                // Potential circular dependency or just two models referencing each other
                // Add one and continue
                $firstModel = array_key_first($unorderedModels);
                $orderedModels[$firstModel] = $unorderedModels[$firstModel];
                unset($unorderedModels[$firstModel]);
            }
        }

        // Add any remaining
        foreach ($unorderedModels as $modelName => $modelData) {
            $orderedModels[$modelName] = $modelData;
        }

        $this->models = $orderedModels;
    }
}