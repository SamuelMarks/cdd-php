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
    $nl = chr(10);
    if ($method === 'additionalOperations' && is_array($operation)) {
        $out = '';
        foreach ($operation as $addMethod => $addOp) {
            $out .= emit((string) $addMethod, $path, is_array($addOp) ? $addOp : [], $composable) . $nl;
        }
        return trim($out);
    }

    $m = ucfirst(strtolower($method));
    $opId = \Cdd\Parameters\sanitize_var_name((string) ($operation['operationId'] ?? "{$m}Route"));
    $status = resolve_expected_status($operation);
    $exportedMethod = var_export($method, true);
    $exportedPath = var_export($path, true);

    $bodyStr = '[]';
    if (in_array(strtolower($method), ['post', 'put', 'patch'], true)) {
        if (isset($operation['requestBody']['content']['application/json']['schema']['$ref'])) {
            $parts = explode('/', (string) $operation['requestBody']['content']['application/json']['schema']['$ref']);
            $schemaName = (string) end($parts);
            $exportedSchema = var_export($schemaName, true);
            $bodyStr = "\$mocks[{$exportedSchema}] ?? []";
        } else {
            $bodyStr = "['dummy' => 'payload']";
        }
    }

    if ($composable) {
        $out = "    '{$opId}' => function(\$client, array \$mocks = []) {" . $nl;
        if ($bodyStr !== '[]') {
            $out .= "        \$response = \$client->call({$exportedMethod}, {$exportedPath}, {$bodyStr});" . $nl;
        } else {
            $out .= "        \$response = \$client->call({$exportedMethod}, {$exportedPath});" . $nl;
        }
        $out .= "        return \$response->status() === {$status};" . $nl;
        $out .= "    }," . $nl;
    } else {
        $out = "    public function test{$opId}(): void {" . $nl;
        $out .= "        \$mocks = file_exists(__DIR__ . '/../../src/mocks.php') ? require __DIR__ . '/../../src/mocks.php' : [];" . $nl;
        if ($bodyStr !== '[]') {
            $out .= "        \$response = \$this->call({$exportedMethod}, {$exportedPath}, {$bodyStr});" . $nl;
        } else {
            $out .= "        \$response = \$this->call({$exportedMethod}, {$exportedPath});" . $nl;
        }
        $out .= "        \$this->assertEquals({$status}, \$response->status());" . $nl;
        $out .= "        \$this->assertIsArray(\$response->body());" . $nl;
        $out .= "    }" . $nl;
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
    $nl = chr(10);
    $opId = \Cdd\Parameters\sanitize_var_name((string) ($operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path)));
    $controllerClass = ucfirst($opId) . 'Controller';
    $className = ucfirst($opId) . 'Test';
    $bs = chr(92);
    $defaultStatus = resolve_expected_status($operation);

    $code = '<?php' . $nl . $nl . 'declare(strict_types=1);' . $nl . $nl;
    $code .= "namespace Api{$bs}Tests{$bs}Routes;" . $nl . $nl;
    $code .= "use PHPUnit{$bs}Framework{$bs}TestCase;" . $nl;
    $code .= "use Api{$bs}Controllers{$bs}{$controllerClass};" . $nl . $nl;
    $code .= "class {$className} extends TestCase {" . $nl;
    $code .= "    protected function call(string \$method, string \$path, array \$body = []): object {" . $nl;
    $code .= "        \$controllerFile = __DIR__ . '/../../src/Controllers/{$controllerClass}.php';" . $nl;
    $code .= "        if (file_exists(\$controllerFile)) {" . $nl;
    $code .= "            require_once \$controllerFile;" . $nl;
    $code .= "        }" . $nl;
    $code .= "        \$status = 404;" . $nl;
    $code .= "        \$decodedBody = [];" . $nl;
    $code .= "        if (class_exists({$controllerClass}::class)) {" . $nl;
    $code .= "            \$ctrl = new {$controllerClass}();" . $nl;
    $code .= "            if (!method_exists(\$ctrl, '__invoke')) {" . $nl;
    $code .= "                \$status = 500;" . $nl;
    $code .= "            } else {" . $nl;
    $code .= "                \$status = {$defaultStatus};" . $nl;
    $code .= "                ob_start();" . $nl;
    $code .= "                try {" . $nl;
    $code .= "                    \$ref = new {$bs}ReflectionMethod(\$ctrl, '__invoke');" . $nl;
    $code .= "                    \$args = [];" . $nl;
    $code .= "                    foreach (\$ref->getParameters() as \$param) {" . $nl;
    $code .= "                        if (\$param->getName() === 'body') {" . $nl;
    $code .= "                            \$args[] = \$body;" . $nl;
    $code .= "                        } elseif (\$param->allowsNull()) {" . $nl;
    $code .= "                            \$args[] = null;" . $nl;
    $code .= "                        } else {" . $nl;
    $code .= "                            \$args[] = 1;" . $nl;
    $code .= "                        }" . $nl;
    $code .= "                    }" . $nl;
    $code .= "                    \$ret = \$ctrl->__invoke(...\$args);" . $nl;
    $code .= "                    \$rawOut = (string) ob_get_clean();" . $nl;
    $code .= "                    if (is_array(\$ret)) {" . $nl;
    $code .= "                        if (isset(\$ret['status']) && is_int(\$ret['status'])) {" . $nl;
    $code .= "                            \$status = \$ret['status'];" . $nl;
    $code .= "                            \$decodedBody = is_array(\$ret['data'] ?? null) ? \$ret['data'] : \$ret;" . $nl;
    $code .= "                        } else {" . $nl;
    $code .= "                            \$decodedBody = \$ret;" . $nl;
    $code .= "                        }" . $nl;
    $code .= "                    } elseif (\$rawOut !== '') {" . $nl;
    $code .= "                        \$parsed = json_decode(\$rawOut, true);" . $nl;
    $code .= "                        \$decodedBody = is_array(\$parsed) ? \$parsed : ['raw' => \$rawOut];" . $nl;
    $code .= "                    }" . $nl;
    $code .= "                    if (isset(\$decodedBody['error'])) {" . $nl;
    $code .= "                        \$status = \$decodedBody['error'] === 'Not found' ? 404 : (int) (\$decodedBody['status'] ?? 400);" . $nl;
    $code .= "                    }" . $nl;
    $code .= "                } catch ({$bs}Throwable \$e) {" . $nl;
    $code .= "                    ob_end_clean();" . $nl;
    $code .= "                    \$status = 500;" . $nl;
    $code .= "                    \$decodedBody = ['error' => \$e->getMessage()];" . $nl;
    $code .= "                }" . $nl;
    $code .= "            }" . $nl;
    $code .= "        }" . $nl;
    $code .= "        return new class(\$status, \$method, \$path, \$decodedBody) {" . $nl;
    $code .= "            private int \$status;" . $nl;
    $code .= "            private string \$method;" . $nl;
    $code .= "            private string \$path;" . $nl;
    $code .= "            private array \$body;" . $nl;
    $code .= "            public function __construct(int \$status, string \$method, string \$path, array \$body) {" . $nl;
    $code .= "                \$this->status = \$status;" . $nl;
    $code .= "                \$this->method = \$method;" . $nl;
    $code .= "                \$this->path = \$path;" . $nl;
    $code .= "                \$this->body = \$body;" . $nl;
    $code .= "            }" . $nl;
    $code .= "            public function status(): int { return \$this->status; }" . $nl;
    $code .= "            public function method(): string { return \$this->method; }" . $nl;
    $code .= "            public function path(): string { return \$this->path; }" . $nl;
    $code .= "            public function body(): array { return \$this->body; }" . $nl;
    $code .= "        };" . $nl;
    $code .= "    }" . $nl . $nl;

    $code .= \Cdd\Tests\emit($method, $path, $operation, false);
    $code .= '}' . $nl;
    return ["{$className}.php" => $code];
}
