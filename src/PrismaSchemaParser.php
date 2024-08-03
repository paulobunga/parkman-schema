<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Str;

class PrismaSchemaParser
{
    protected $schema;
    protected $models = [];
    protected $operations = [];

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function parse()
    {
        $lines = explode("\n", $this->schema);
        $currentModel = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/model\s+(\w+)\s*{/', $line, $matches)) {
                $currentModel = $matches[1];
                $this->models[$currentModel] = ['fields' => [], 'relations' => []];
            } elseif ($currentModel && preg_match('/(\w+)\s+(\w+)(\??)(\s*@(\w+)(\(.*\))?)?/', $line, $matches)) {
                $field = [
                    'name' => $matches[1],
                    'type' => $matches[2],
                    'nullable' => !empty($matches[3]),
                    'attribute' => isset($matches[5]) ? $matches[5] : null,
                    'attributeParams' => isset($matches[6]) ? trim($matches[6], '()') : null,
                ];
                $this->models[$currentModel]['fields'][] = $field;

                if ($field['attribute'] === 'relation') {
                    $this->models[$currentModel]['relations'][] = $field;
                }
            } elseif (preg_match('/@((\w+)(\(.*\))?)/', $line, $matches)) {
                $operation = [
                    'type' => $matches[2],
                    'params' => isset($matches[3]) ? trim($matches[3], '()') : null,
                ];
                $this->operations[] = $operation;
            }
        }

        $this->reorderModels();
        return ['models' => $this->models, 'operations' => $this->operations];
    }

    protected function reorderModels()
    {
        $orderedModels = [];
        $unorderedModels = $this->models;

        while (!empty($unorderedModels)) {
            $addedModels = [];

            foreach ($unorderedModels as $modelName => $modelData) {
                $canAdd = true;

                foreach ($modelData['relations'] as $relation) {
                    $relatedModel = $this->extractRelatedModel($relation);
                    if (!isset($orderedModels[$relatedModel]) && $relatedModel !== $modelName) {
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
                // There's a circular dependency, add the first unordered model
                $firstModel = array_key_first($unorderedModels);
                $orderedModels[$firstModel] = $unorderedModels[$firstModel];
                unset($unorderedModels[$firstModel]);
            }
        }

        $this->models = $orderedModels;
    }

    protected function extractRelatedModel($relation)
    {
        $params = explode(',', $relation['attributeParams']);
        return trim($params[0]);
    }
}