<?php

declare(strict_types=1);

namespace Cdd\Components;

/**
 * Emits code string from a Components Object.
 *
 * @param array $components The components object
 * @param string $existingCode Existing PHP code
 * @return string The generated code for the components
 */
function emit(array $components, string $existingCode = ''): string {
    $out = $existingCode !== '' ? $existingCode : "<?php\n\n";
    
    if (isset($components['schemas'])) {
        foreach ($components['schemas'] as $schemaName => $schemaDef) {
            if (strpos($out, "class $schemaName ") === false && strpos($out, "class $schemaName\n") === false) {
                $out .= \Cdd\Schemas\emit($schemaName, $schemaDef) . "\n";
            }
        }
    }
    
    $types = [
        'parameters' => '@parameter',
        'responses' => '@response',
        'requestBodies' => '@requestBody',
        'headers' => '@header',
        'securitySchemes' => '@securityScheme',
        'pathItems' => '@pathItem',
        'callbacks' => '@callback',
        'links' => '@link',
        'mediaTypes' => '@mediaType'
    ];
    
    foreach ($types as $compType => $docTag) {
        if (isset($components[$compType])) {
            foreach ($components[$compType] as $name => $compDef) {
                if (strpos($out, "class $name ") === false && strpos($out, "class $name\n") === false) {
                    $doc = "/**\n * {$docTag}\n";
                    if ($compType === 'parameters') {
                        $doc .= " * @in " . ($compDef['in'] ?? 'query') . "\n";
                        $doc .= " * @name " . ($compDef['name'] ?? $name) . "\n";
                        if (!empty($compDef['required'])) $doc .= " * @required true\n";
                    } elseif ($compType === 'securitySchemes') {
                        $doc .= " * @type " . ($compDef['type'] ?? 'http') . "\n";
                        if (isset($compDef['scheme'])) $doc .= " * @scheme " . $compDef['scheme'] . "\n";
                        if (isset($compDef['in'])) $doc .= " * @in " . $compDef['in'] . "\n";
                        if (isset($compDef['name'])) $doc .= " * @name " . $compDef['name'] . "\n";
                        if (isset($compDef['bearerFormat'])) $doc .= " * @bearerFormat " . $compDef['bearerFormat'] . "\n";
                        if (isset($compDef['openIdConnectUrl'])) $doc .= " * @openIdConnectUrl " . $compDef['openIdConnectUrl'] . "\n";
                        if (isset($compDef['flows'])) {
                            foreach ($compDef['flows'] as $flowType => $flow) {
                                $doc .= " * @flow {$flowType} " . json_encode($flow) . "\n";
                            }
                        }
                    } else {
                        if (isset($compDef['description']) && $compDef['description'] !== '') {
                            $doc .= " * " . str_replace("\n", "\n * ", $compDef['description']) . "\n";
                        }
                    }
                    $doc .= " */\n";
                    
                    $schemaDef = [];
                    if (isset($compDef['schema'])) {
                        $schemaDef = $compDef['schema'];
                    } elseif (isset($compDef['content']['application/json']['schema'])) {
                        $schemaDef = $compDef['content']['application/json']['schema'];
                    }
                    
                    // We emit the class with properties if it has a schema
                    if (!empty($schemaDef)) {
                        $classCode = \Cdd\Schemas\emit($name, $schemaDef);
                        $classCode = preg_replace('/^\/\*\*.*?\*\/\n/s', '', $classCode); // remove original docblock
                        $out .= $doc . $classCode . "\n";
                    } else {
                        $out .= $doc . "class $name {}\n\n";
                    }
                }
            }
        }
    }
    
    return $out;
}
