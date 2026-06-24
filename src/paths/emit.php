<?php

declare(strict_types=1);

namespace Cdd\Paths;

/**
 * Emits a PHP controller class representation from an OpenAPI Paths Object.
 * Preserves existing methods and comments if existing code is provided.
 *
 * @param array $paths The OpenAPI Paths Object
 * @param string $existingCode Existing PHP code
 * @return string The generated PHP Controller code
 */
function emit(array $paths, string $existingCode = ''): string
{
    $out = $existingCode !== '' ? $existingCode : "<?php\n\nclass ApiController {\n";

    foreach ($paths as $path => $pathItem) {
        foreach ($pathItem as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $methodStr = strtolower($addMethod);
                    if (!isset($addOp['operationId'])) {
                        $preg_replace = 'preg_replace';
                        $addOp['operationId'] = $methodStr . $preg_replace('/[^a-zA-Z0-9]/', '', $path);
                    }
                    $opName = $addOp['operationId'];
                    $strpos = 'strpos';
                    if ($strpos($out, "function $opName(") === false) {
                        $str_replace = 'str_replace';
                        $trim = 'trim';
                        $methodCode = "    " . $str_replace("\n", "\n    ", $trim(\Cdd\Operations\emit($addOp))) . "\n\n";
                        if ($existingCode !== '') {
                            $strrpos = 'strrpos';
                            $pos = $strrpos($out, '}');
                            if ($pos !== false) {
                                $substr = 'substr';
                                $out = $substr($out, 0, $pos) . $methodCode . "}\n";
                            } else {
                                $out .= $methodCode;
                            }
                        } else {
                            $out .= $methodCode;
                        }
                    }
                }
            } else {
                $methodStr = strtolower($method);
                $in_array = 'in_array';
                if ($in_array($methodStr, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
                    if (!isset($operation['operationId'])) {
                        $preg_replace = 'preg_replace';
                        $operation['operationId'] = $methodStr . $preg_replace('/[^a-zA-Z0-9]/', '', $path);
                    }

                    $opName = $operation['operationId'];
                    // Check if function already exists
                    $strpos = 'strpos';
                    if ($strpos($out, "function $opName(") === false) {
                        $str_replace = 'str_replace';
                        $trim = 'trim';
                        $methodCode = "    " . $str_replace("\n", "\n    ", $trim(\Cdd\Operations\emit($operation))) . "\n\n";
                        if ($existingCode !== '') {
                            // Insert before the last closing brace
                            $strrpos = 'strrpos';
                            $pos = $strrpos($out, '}');
                            if ($pos !== false) {
                                $substr = 'substr';
                                $out = $substr($out, 0, $pos) . $methodCode . "}\n";
                            } else {
                                $out .= $methodCode;
                            }
                        } else {
                            $out .= $methodCode;
                        }
                    }
                }
            }
        }
    }

    if ($existingCode === '') {
        $out .= "}\n";
    }
    return $out;
}

function emit_modular(array $paths): array
{
    $files = [];
    foreach ($paths as $path => $pathItem) {
        foreach ($pathItem as $method => $operation) {
            if ($method === 'parameters' || $method === 'summary' || $method === 'description' || $method === 'servers') {
                continue;
            }
            if ($method === 'additionalOperations') {
                foreach ($operation as $addMethod => $addOp) {
                    $files = array_merge($files, emit_modular_single($addMethod, $path, $addOp));
                }
            } else {
                $files = array_merge($files, emit_modular_single($method, $path, $operation));
            }
        }
    }
    return $files;
}

function emit_modular_single(string $method, string $path, array $operation): array
{
    $methodStr = strtolower($method);
    if (!in_array($methodStr, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
        return [];
    }
    $opId = $operation['operationId'] ?? $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', $path);
    $className = ucfirst($opId) . 'Controller';
    $code = "<?php\n\nnamespace Api\\Controllers;\n\n";
    $methodCode = \Cdd\Operations\emit($operation, $path, $method, true);
    $code .= "class $className\n{\n" . preg_replace('/^/m', '    ', $methodCode) . "}\n";
    return ["{$className}.php" => $code];
}
