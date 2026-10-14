<?php

/**
 * Emits PHP controller classes from OpenAPI Paths Objects.
 *
 * @package Cdd\Paths
 */

declare(strict_types=1);

namespace Cdd\Paths;

/**
 * Appends a method block to a PHP class source string before its closing brace.
 *
 * @param string $classCode The target class PHP code.
 * @param string $methodCode The indented method code to append.
 * @param bool $hasClosingBrace Whether the target class string already has a closing brace.
 * @return string The updated PHP class code.
 */
function append_method_to_class(string $classCode, string $methodCode, bool $hasClosingBrace): string
{
    if ($hasClosingBrace) {
        $pos = strrpos($classCode, '}');
        if ($pos !== false) {
            return substr($classCode, 0, $pos) . $methodCode . "}
";
        }
    }
    return $classCode . $methodCode;
}

/**
 * Emits a PHP controller class representation from an OpenAPI Paths Object.
 * Preserves existing methods and comments if existing code is provided.
 *
 * @param array<string, array<string, mixed>> $paths The OpenAPI Paths Object.
 * @param string $existingCode Existing PHP code.
 * @return string The generated PHP Controller code.
 */
function emit(array $paths, string $existingCode = ''): string
{
    $out = $existingCode !== '' ? $existingCode : "<?php

class ApiController {
";
    $validMethods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'];

    foreach ($paths as $path => $pathItem) {
        foreach ($pathItem as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $methodStr = strtolower((string) $addMethod);
                    if (!isset($addOp['operationId'])) {
                        $addOp['operationId'] = $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', (string) $path);
                    }
                    $opName = (string) $addOp['operationId'];
                    if (strpos($out, "function $opName(") === false) {
                        $nl = chr(10);
                        $methodCode = "    " . str_replace($nl, $nl . "    ", trim(\Cdd\Operations\emit($addOp))) . $nl . $nl;
                        $out = append_method_to_class($out, $methodCode, $existingCode !== '');
                    }
                }
            } else {
                $methodStr = strtolower((string) $method);
                if (in_array($methodStr, $validMethods, true)) {
                    if (!isset($operation['operationId'])) {
                        $operation['operationId'] = $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', (string) $path);
                    }
                    $opName = (string) $operation['operationId'];
                    if (strpos($out, "function $opName(") === false) {
                        $nl = chr(10);
                        $methodCode = "    " . str_replace($nl, $nl . "    ", trim(\Cdd\Operations\emit($operation))) . $nl . $nl;
                        $out = append_method_to_class($out, $methodCode, $existingCode !== '');
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

/**
 * Emits modular single-action controller files from an OpenAPI Paths Object.
 *
 * @param array<string, array<string, mixed>> $paths The OpenAPI Paths Object.
 * @return array<string, string> Map of controller filenames to generated PHP code.
 */
function emit_modular(array $paths): array
{
    $files = [];
    foreach ($paths as $path => $pathItem) {
        foreach ($pathItem as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $files = array_merge($files, emit_modular_single((string) $addMethod, (string) $path, $addOp));
                }
            } elseif (is_array($operation)) {
                $files = array_merge($files, emit_modular_single((string) $method, (string) $path, $operation));
            }
        }
    }
    return $files;
}

/**
 * Emits a single modular controller file for an individual operation.
 *
 * @param string $method The HTTP method.
 * @param string $path The URL path pattern.
 * @param array<string, mixed> $operation The OpenAPI Operation Object.
 * @return array<string, string> Map of controller filename to generated PHP code.
 */
function emit_modular_single(string $method, string $path, array $operation): array
{
    $methodStr = strtolower($method);
    if (!in_array($methodStr, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'], true)) {
        return [];
    }
    $opId = (string) ($operation['operationId'] ?? $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', $path));
    $className = ucfirst($opId) . 'Controller';
    $code = "<?php

namespace Api\Controllers;

";
    $methodCode = \Cdd\Operations\emit($operation, $path, $method, true);
    $code .= "class $className
{
" . preg_replace('/^/m', '    ', $methodCode) . "}
";
    return ["{$className}.php" => $code];
}
