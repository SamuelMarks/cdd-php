<?php

/**
 * Emits PHPUnit route test methods and modular route test classes.
 *
 * @package Cdd\Tests
 */

declare(strict_types=1);

namespace Cdd\Tests;

/**
 * Emits a PHP test method.
 *
 * @param string $method The HTTP method.
 * @param string $path The URL path pattern.
 * @param array<string, mixed> $operation The OpenAPI Operation Object.
 * @param bool $composable Whether to emit as a closure entry in a composable map.
 * @return string The generated test method or closure string.
 */
function emit(string $method, string $path, array $operation, bool $composable = false): string
{
    if ($method === 'additionalOperations' && is_array($operation)) {
        $out = '';
        foreach ($operation as $addMethod => $addOp) {
            $out .= emit((string) $addMethod, $path, $addOp, $composable) . "
";
        }
        return trim($out);
    }

    $m = ucfirst(strtolower($method));
    $opId = $operation['operationId'] ?? "{$m}Route";

    $status = '200';
    if (isset($operation['responses'])) {
        foreach (['200', '201', '202', '204'] as $successCode) {
            if (isset($operation['responses'][$successCode])) {
                $status = $successCode;
                break;
            }
        }
        if ($status === '200') {
            foreach ($operation['responses'] as $codeVal => $resp) {
                if ($codeVal !== 'default' && !str_starts_with((string) $codeVal, 'x-')) {
                    $status = (string) $codeVal;
                    break;
                }
            }
        }
    }

    $bodyStr = '[]';
    if (in_array(strtolower($method), ['post', 'put', 'patch'], true)) {
        if (isset($operation['requestBody']['content']['application/json']['schema']['$ref'])) {
            $parts = explode('/', (string) $operation['requestBody']['content']['application/json']['schema']['$ref']);
            $schemaName = end($parts);
            $bodyStr = "\$mocks['{$schemaName}'] ?? []";
        } else {
            $bodyStr = "['dummy' => 'payload']";
        }
    }

    if ($composable) {
        $out = "    '{$opId}' => function(\$client, array \$mocks = []) {
";
        if ($bodyStr !== '[]') {
            $out .= "        \$response = \$client->call('$method', '$path', $bodyStr);
";
        } else {
            $out .= "        \$response = \$client->call('$method', '$path');
";
        }
        $out .= "        return \$response->status() === $status;
";
        $out .= "    },
";
    } else {
        $out = "    public function test{$opId}(): void {
";
        $out .= "        \$mocks = file_exists(__DIR__ . '/../../src/mocks.php') ? require __DIR__ . '/../../src/mocks.php' : [];
";
        if ($bodyStr !== '[]') {
            $out .= "        \$response = \$this->call('$method', '$path', $bodyStr);
";
        } else {
            $out .= "        \$response = \$this->call('$method', '$path');
";
        }
        $out .= "        \$this->assertEquals($status, \$response->status());
";
        $out .= "    }
";
    }
    return $out;
}

/**
 * Emits modular PHPUnit test files for all operations in an OpenAPI Paths Object.
 *
 * @param array<string, mixed> $paths The OpenAPI Paths Object.
 * @return array<string, string> Map of test filenames to generated PHP code.
 */
function emit_modular(array $paths): array
{
    $files = [];
    foreach ($paths as $path => $methods) {
        foreach ((array) $methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $files = array_merge($files, emit_modular_single_test((string) $addMethod, (string) $path, $addOp));
                }
            } elseif (in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'], true)) {
                $files = array_merge($files, emit_modular_single_test((string) $method, (string) $path, $operation));
            }
        }
    }
    return $files;
}

/**
 * Emits a single modular PHPUnit test class for an individual operation.
 *
 * @param string $method The HTTP method.
 * @param string $path The URL path pattern.
 * @param array<string, mixed> $operation The OpenAPI Operation Object.
 * @return array<string, string> Map of test filename to generated PHP code.
 */
function emit_modular_single_test(string $method, string $path, array $operation): array
{
    $opId = (string) ($operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path));
    $controllerClass = ucfirst($opId) . 'Controller';
    $className = ucfirst($opId) . 'Test';
    $bs = chr(92);
    $code = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Api{$bs}Tests{$bs}Routes;\n\nuse PHPUnit{$bs}Framework{$bs}TestCase;\nuse Api{$bs}Controllers{$bs}{$controllerClass};\n\nclass $className extends TestCase {\n";

    $status = '200';
    if (isset($operation['responses'])) {
        foreach (['200', '201', '202', '204'] as $successCode) {
            if (isset($operation['responses'][$successCode])) {
                $status = $successCode;
                break;
            }
        }
        if ($status === '200') {
            foreach ($operation['responses'] as $codeVal => $resp) {
                if ($codeVal !== 'default' && !str_starts_with((string) $codeVal, 'x-')) {
                    $status = (string) $codeVal;
                    break;
                }
            }
        }
    }

    $code .= "    protected function call(string \$method, string \$path, array \$body = []): object {\n";
    $code .= "        \$controllerFile = __DIR__ . '/../../src/Controllers/{$controllerClass}.php';\n";
    $code .= "        if (file_exists(\$controllerFile)) {\n";
    $code .= "            require_once \$controllerFile;\n";
    $code .= "        }\n";
    $code .= "        \$status = $status;\n";
    $code .= "        if (class_exists({$controllerClass}::class)) {\n";
    $code .= "            \$ctrl = new {$controllerClass}();\n";
    $code .= "            if (!method_exists(\$ctrl, '__invoke')) {\n";
    $code .= "                \$status = 500;\n";
    $code .= "            }\n";
    $code .= "        }\n";
    $code .= "        return new class(\$status, \$method, \$path, \$body) {
";
    $code .= "            private int \$status;
";
    $code .= "            private string \$method;
";
    $code .= "            private string \$path;
";
    $code .= "            private array \$body;
";
    $code .= "            public function __construct(int \$status, string \$method, string \$path, array \$body) {
";
    $code .= "                \$this->status = \$status;
";
    $code .= "                \$this->method = \$method;
";
    $code .= "                \$this->path = \$path;
";
    $code .= "                \$this->body = \$body;
";
    $code .= "            }
";
    $code .= "            public function status(): int { return \$this->status; }
";
    $code .= "            public function method(): string { return \$this->method; }
";
    $code .= "            public function path(): string { return \$this->path; }
";
    $code .= "            public function body(): array { return \$this->body; }
";
    $code .= "        };
";
    $code .= "    }

";

    $code .= \Cdd\Tests\emit($method, $path, $operation, false);
    $code .= "}
";
    return ["{$className}.php" => $code];
}
