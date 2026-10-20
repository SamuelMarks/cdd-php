<?php

/**
 * Emits Laravel-style route definitions from OpenAPI Paths Objects.
 *
 * @package Cdd\Routes
 */

declare(strict_types=1);

namespace Cdd\Routes;

/**
 * Emits a PHP route file structure from OpenAPI paths array.
 *
 * @param array<string, mixed> $paths The OpenAPI Paths Object.
 * @param string $existingCode Optional existing routes PHP code.
 * @return string The generated routes PHP code.
 */
function emit(array $paths, string $existingCode = ''): string
{
    $validMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'];
    if ($existingCode !== '') {
        $out = $existingCode;
        foreach ($paths as $path => $methods) {
            $exportedPath = var_export((string) $path, true);
            foreach ((array) $methods as $method => $operation) {
                if ($method === 'additionalOperations' && is_array($operation)) {
                    foreach ($operation as $addMethod => $addOp) {
                        $m = strtolower((string) $addMethod);
                        $controller = (string) ($addOp['operationId'] ?? 'SomeController@action');
                        $exportedCtrl = var_export($controller, true);
                        if (strpos($out, "Route::$m($exportedPath,") === false && strpos($out, 'Route::' . $m . '("' . $path . '",') === false) {
                            $out .= "Route::$m($exportedPath, $exportedCtrl);
";
                        }
                    }
                } else {
                    $method = strtolower((string) $method);
                    if (in_array($method, $validMethods, true)) {
                        $controller = (string) ($operation['operationId'] ?? 'SomeController@action');
                        $exportedCtrl = var_export($controller, true);
                        if (strpos($out, "Route::$method($exportedPath,") === false && strpos($out, 'Route::' . $method . '("' . $path . '",') === false) {
                            $out .= "Route::$method($exportedPath, $exportedCtrl);
";
                        }
                    }
                }
            }
        }
        return $out;
    }

    $out = "<?php

use Illuminate\Support\Facades\Route;

";
    foreach ($paths as $path => $methods) {
        $exportedPath = var_export((string) $path, true);
        foreach ((array) $methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $m = strtolower((string) $addMethod);
                    $controller = (string) ($addOp['operationId'] ?? 'SomeController@action');
                    $exportedCtrl = var_export($controller, true);
                    $out .= "Route::$m($exportedPath, $exportedCtrl);
";
                }
            } else {
                $method = strtolower((string) $method);
                if (in_array($method, $validMethods, true)) {
                    $controller = (string) ($operation['operationId'] ?? 'SomeController@action');
                    $exportedCtrl = var_export($controller, true);
                    $out .= "Route::$method($exportedPath, $exportedCtrl);
";
                }
            }
        }
    }
    return $out;
}

/**
 * Emits modular route files grouped by top-level URL path segment.
 *
 * @param array<string, mixed> $paths The OpenAPI Paths Object.
 * @return array<string, string> Map of route filenames to generated PHP code.
 */
function emit_modular(array $paths): array
{
    $files = [];
    $grouped = [];

    foreach ($paths as $path => $methods) {
        $parts = explode('/', trim((string) $path, '/'));
        $group = !empty($parts[0]) ? ucfirst((string) preg_replace('/[^a-zA-Z0-9]/', '', $parts[0])) : 'Default';

        if (!isset($grouped[$group])) {
            $grouped[$group] = "<?php

use Illuminate\Support\Facades\Route;

";
        }

        foreach ((array) $methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $grouped[$group] .= emit_modular_single_route((string) $addMethod, (string) $path, $addOp);
                }
            } else {
                $grouped[$group] .= emit_modular_single_route((string) $method, (string) $path, $operation);
            }
        }
    }

    foreach ($grouped as $group => $content) {
        $files["{$group}Routes.php"] = $content;
    }

    return $files;
}

/**
 * Emits a single modular route registration line.
 *
 * @param string $method The HTTP method.
 * @param string $path The URL path pattern.
 * @param array<string, mixed> $operation The OpenAPI Operation Object.
 * @return string The emitted `Route::...` statement.
 */
function emit_modular_single_route(string $method, string $path, array $operation): string
{
    $methodStr = strtolower($method);
    if (!in_array($methodStr, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
        return '';
    }
    $opId = (string) ($operation['operationId'] ?? $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', $path));
    $className = ucfirst((string) preg_replace('/[^a-zA-Z0-9_]/', '', $opId)) . 'Controller';
    $exportedPath = var_export($path, true);
    $bs = chr(92);
    return "Route::$methodStr($exportedPath, [{$bs}Api{$bs}Controllers{$bs}{$className}::class, '__invoke']);
";
}
