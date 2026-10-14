<?php

/**
 * Emits PHP class definitions from OpenAPI Components Objects.
 *
 * @package Cdd\Components
 */

declare(strict_types=1);

namespace Cdd\Components;

/**
 * Emits code string from a Components Object.
 *
 * @param array<string, mixed> $components The components object.
 * @param string $existingCode Existing PHP code.
 * @return string The generated code for the components.
 */
function emit(array $components, string $existingCode = ''): string
{
    $out = $existingCode !== '' ? $existingCode : "<?php

";

    if (isset($components['schemas']) && is_array($components['schemas'])) {
        foreach ($components['schemas'] as $schemaName => $schemaDef) {
            if (strpos($out, "class $schemaName ") === false && strpos($out, "class $schemaName
") === false) {
                $out .= \Cdd\Schemas\emit((string) $schemaName, $schemaDef) . "
";
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
        'mediaTypes' => '@mediaType',
    ];

    foreach ($types as $compType => $docTag) {
        if (isset($components[$compType]) && is_array($components[$compType])) {
            foreach ($components[$compType] as $name => $compDef) {
                if (strpos($out, "class $name ") === false && strpos($out, "class $name
") === false) {
                    $doc = "/**
 * {$docTag}
";
                    if ($compType === 'parameters') {
                        $doc .= " * @in " . ($compDef['in'] ?? 'query') . "
";
                        $doc .= " * @name " . ($compDef['name'] ?? $name) . "
";
                        if (!empty($compDef['required'])) {
                            $doc .= " * @required true
";
                        }
                    } elseif ($compType === 'securitySchemes') {
                        $doc .= " * @type " . ($compDef['type'] ?? 'http') . "
";
                        if (isset($compDef['scheme'])) {
                            $doc .= " * @scheme " . $compDef['scheme'] . "
";
                        }
                        if (isset($compDef['in'])) {
                            $doc .= " * @in " . $compDef['in'] . "
";
                        }
                        if (isset($compDef['name'])) {
                            $doc .= " * @name " . $compDef['name'] . "
";
                        }
                        if (isset($compDef['bearerFormat'])) {
                            $doc .= " * @bearerFormat " . $compDef['bearerFormat'] . "
";
                        }
                        if (isset($compDef['openIdConnectUrl'])) {
                            $doc .= " * @openIdConnectUrl " . $compDef['openIdConnectUrl'] . "
";
                        }
                        if (isset($compDef['flows']) && is_array($compDef['flows'])) {
                            foreach ($compDef['flows'] as $flowType => $flow) {
                                $doc .= " * @flow {$flowType} " . json_encode($flow) . "
";
                            }
                        }
                    } else {
                        if (isset($compDef['description']) && $compDef['description'] !== '') {
                            $nl = chr(10);
                            $doc .= " * " . str_replace($nl, $nl . " * ", (string) $compDef['description']) . $nl;
                        }
                        if ($compType === 'mediaTypes') {
                            if (isset($compDef['itemSchema']['$ref'])) {
                                $refParts = explode('/', (string) $compDef['itemSchema']['$ref']);
                                $doc .= " * @itemSchema " . end($refParts) . "\n";
                            }
                            if (isset($compDef['itemEncoding']) && is_array($compDef['itemEncoding'])) {
                                $doc .= " * @itemEncoding " . ($compDef['itemEncoding']['contentType'] ?? json_encode($compDef['itemEncoding'])) . "\n";
                                $doc .= " * @encodingPhp " . str_replace("\n", ' ', \Cdd\Encoding\emit($compDef['itemEncoding'])) . "\n";
                            }
                        }
                    }
                    $doc .= " */\n";

                    $schemaDef = [];
                    if (isset($compDef['schema'])) {
                        $schemaDef = $compDef['schema'];
                    } elseif (isset($compDef['content']['application/json']['schema'])) {
                        $schemaDef = $compDef['content']['application/json']['schema'];
                    }

                    if (!empty($schemaDef)) {
                        $classCode = \Cdd\Schemas\emit((string) $name, $schemaDef);
                        $classCode = (string) preg_replace('/^\/\*\*.*?\*\/\s*/s', '', $classCode);
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
