<?php

/**
 * Emits PHPUnit route test methods and modular route test classes.
 *
 * @package Cdd\Tests
 */

declare(strict_types=1);

namespace Cdd\Tests;

/**
 * Resolves the expected primary HTTP status code for an OpenAPI Operation Object.
 *
 * @param array<string, mixed> $operation The OpenAPI Operation Object.
 * @return int The expected HTTP status code.
 */
function resolve_expected_status(array $operation): int
{
    if (isset($operation['responses']) && is_array($operation['responses'])) {
        foreach (['200', '201', '202', '204'] as $successCode) {
            if (isset($operation['responses'][$successCode])) {
                return (int) $successCode;
            }
        }
        foreach ($operation['responses'] as $codeVal => $resp) {
            $codeStr = (string) $codeVal;
            if ($codeStr !== 'default' && !str_starts_with($codeStr, 'x-') && is_numeric($codeStr)) {
                return (int) $codeStr;
            }
        }
    }
    return 200;
}

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
            $out .= emit((string) $addMethod, $path, is_array($addOp) ? $addOp : [], $composable) . "
";
        }
        return trim($out);
    }

    $m = ucfirst(strtolower($method));
    $opId = (string) ($operation['operationId'] ?? "{$m}Route");
    $status = resolve_expected_status($operation);

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
        $out .= "        \$this->assertIsArray(\$response->body());
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
                    $files = array_merge($files, emit_modular_single_test((string) $addMethod, (string) $path, is_array($addOp) ? $addOp : []));
                }
            } elseif (in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'], true)) {
                $files = array_merge($files, emit_modular_single_test((string) $method, (string) $path, is_array($operation) ? $operation : []));
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
    $defaultStatus = resolve_expected_status($operation);

    $code = "<?php

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Routes;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}Controllers{$bs}{$controllerClass};

class $className extends TestCase {
";
    $code .= "    protected function call(string \$method, string \$path, array \$body = []): object {
";
    $code .= "        \$controllerFile = __DIR__ . '/../../src/Controllers/{$controllerClass}.php';
";
    $code .= "        if (file_exists(\$controllerFile)) {
";
    $code .= "            require_once \$controllerFile;
";
    $code .= "        }
";
    $code .= "        \$status = 404;
";
    $code .= "        \$decodedBody = [];
";
    $code .= "        if (class_exists({$controllerClass}::class)) {
";
    $code .= "            \$ctrl = new {$controllerClass}();
";
    $code .= "            if (!method_exists(\$ctrl, '__invoke')) {
";
    $code .= "                \$status = 500;
";
    $code .= "            } else {
";
    $code .= "                \$status = {$defaultStatus};
";
    $code .= "                ob_start();
";
    $code .= "                try {
";
    $code .= "                    \$ref = new {$bs}ReflectionMethod(\$ctrl, '__invoke');
";
    $code .= "                    \$args = [];
";
    $code .= "                    foreach (\$ref->getParameters() as \$param) {
";
    $code .= "                        if (\$param->getName() === 'body') {
";
    $code .= "                            \$args[] = \$body;
";
    $code .= "                        } elseif (\$param->allowsNull()) {
";
    $code .= "                            \$args[] = null;
";
    $code .= "                        } else {
";
    $code .= "                            \$args[] = 1;
";
    $code .= "                        }
";
    $code .= "                    }
";
    $code .= "                    \$ret = \$ctrl->__invoke(...\$args);
";
    $code .= "                    \$rawOut = (string) ob_get_clean();
";
    $code .= "                    if (is_array(\$ret)) {
";
    $code .= "                        \$decodedBody = \$ret;
";
    $code .= "                    } elseif (\$rawOut !== '') {
";
    $code .= "                        \$parsed = json_decode(\$rawOut, true);
";
    $code .= "                        \$decodedBody = is_array(\$parsed) ? \$parsed : ['raw' => \$rawOut];
";
    $code .= "                        if (isset(\$decodedBody['error']) && \$decodedBody['error'] === 'Not found') {
";
    $code .= "                            \$status = 404;
";
    $code .= "                        }
";
    $code .= "                    }
";
    $code .= "                } catch ({$bs}Throwable \$e) {
";
    $code .= "                    ob_end_clean();
";
    $code .= "                    \$status = 500;
";
    $code .= "                    \$decodedBody = ['error' => \$e->getMessage()];
";
    $code .= "                }
";
    $code .= "            }
";
    $code .= "        }
";
    $code .= "        return new class(\$status, \$method, \$path, \$decodedBody) {
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
