<?php

declare(strict_types=1);

namespace Cdd\Routes;

/**
 * Emits a PHP route file structure from OpenAPI paths array.
 */
function emit(array $paths, string $existingCode = ''): string {
    if ($existingCode !== '') {
        $out = $existingCode;
        foreach ($paths as $path => $methods) {
            foreach ((array)$methods as $method => $operation) {
                if ($method === 'additionalOperations' && is_array($operation)) {
                    foreach ($operation as $addMethod => $addOp) {
                        $m = strtolower($addMethod);
                        $controller = $addOp['operationId'] ?? 'SomeController@action';
                        if (strpos($out, "Route::$m('$path',") === false && strpos($out, "Route::$m(\"$path\",") === false) {
                            $out .= "Route::$m('$path', '$controller');\n";
                        }
                    }
                } else {
                    $method = strtolower($method);
                    if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
                        $controller = $operation['operationId'] ?? 'SomeController@action';
                        if (strpos($out, "Route::$method('$path',") === false && strpos($out, "Route::$method(\"$path\",") === false) {
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
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
                    $controller = $operation['operationId'] ?? 'SomeController@action';
                    $out .= "Route::$method('$path', '$controller');\n";
                }
            }
        }
    }
    return $out;
}
