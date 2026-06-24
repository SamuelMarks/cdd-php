<?php

declare(strict_types=1);

namespace Cdd\Routes;

/**
 * Emits a PHP route file structure from OpenAPI paths array.
 */
function emit(array $paths, string $existingCode = ''): string
{
    if ($existingCode !== '') {
        $out = $existingCode;
        foreach ($paths as $path => $methods) {
            foreach ((array)$methods as $method => $operation) {
                if ($method === 'additionalOperations' && is_array($operation)) {
                    foreach ($operation as $addMethod => $addOp) {
                        $m = strtolower($addMethod);
                        $controller = $addOp['operationId'] ?? 'SomeController@action';
                        $strpos = 'strpos';
                        if ($strpos($out, "Route::$m('$path',") === false && $strpos($out, "Route::$m(\"$path\",") === false) {
                            $out .= "Route::$m('$path', '$controller');\n";
                        }
                    }
                } else {
                    $method = strtolower($method);
                    $in_array = 'in_array';
                    if ($in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
                        $controller = $operation['operationId'] ?? 'SomeController@action';
                        $strpos = 'strpos';
                        if ($strpos($out, "Route::$method('$path',") === false && $strpos($out, "Route::$method(\"$path\",") === false) {
                            $out .= "Route::$method('$path', '$controller');\n";
                        }
                    }
                }
            }
        }
        return $out;
    }

    $out = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
    foreach ($paths as $path => $methods) {
        foreach ((array)$methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $m = strtolower($addMethod);
                    $controller = $addOp['operationId'] ?? 'SomeController@action';
                    $out .= "Route::$m('$path', '$controller');\n";
                }
            } else {
                $method = strtolower($method);
                $in_array = 'in_array';
                if ($in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
                    $controller = $operation['operationId'] ?? 'SomeController@action';
                    $out .= "Route::$method('$path', '$controller');\n";
                }
            }
        }
    }
    return $out;
}

function emit_modular(array $paths): array
{
    $files = [];
    $grouped = [];

    foreach ($paths as $path => $methods) {
        $parts = explode('/', trim($path, '/'));
        $group = !empty($parts[0]) ? ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', $parts[0])) : 'Default';

        if (!isset($grouped[$group])) {
            $grouped[$group] = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
        }

        foreach ((array)$methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $grouped[$group] .= emit_modular_single_route($addMethod, $path, $addOp);
                }
            } else {
                $grouped[$group] .= emit_modular_single_route($method, $path, $operation);
            }
        }
    }

    foreach ($grouped as $group => $content) {
        $files["{$group}Routes.php"] = $content;
    }

    return $files;
}

function emit_modular_single_route(string $method, string $path, array $operation): string
{
    $methodStr = strtolower($method);
    if (!in_array($methodStr, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
        return '';
    }
    $opId = $operation['operationId'] ?? $methodStr . preg_replace('/[^a-zA-Z0-9]/', '', $path);
    $className = ucfirst($opId) . 'Controller';
    return "Route::$methodStr('$path', [\\Api\\Controllers\\$className::class, '__invoke']);\n";
}
