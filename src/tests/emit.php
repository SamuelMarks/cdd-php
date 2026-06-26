<?php

declare(strict_types=1);

namespace Cdd\Tests;

/**
 * Emits a PHP test method.
 *
 * @param string $method
 * @param string $path
 * @param array $operation
 * @param bool $composable
 * @return string
 */
function emit(string $method, string $path, array $operation, bool $composable = false): string
{
    if ($method === 'additionalOperations' && is_array($operation)) {
        $out = '';
        foreach ($operation as $addMethod => $addOp) {
            $out .= emit($addMethod, $path, $addOp, $composable) . "\n";
        }
        $trim = 'trim';
        return $trim($out);
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
                if ($codeVal !== 'default' && !str_starts_with((string)$codeVal, 'x-')) {
                    $status = $codeVal;
                    break;
                }
            }
        }
    }

    $bodyStr = "[]";
    if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
        if (isset($operation['requestBody']['content']['application/json']['schema']['$ref'])) {
            $parts = explode('/', $operation['requestBody']['content']['application/json']['schema']['$ref']);
            $schemaName = end($parts);
            $bodyStr = "\$mocks['{$schemaName}'] ?? []";
        } else {
            $bodyStr = "['dummy' => 'payload']";
        }
    }

    if ($composable) {
        $out = "    '{$opId}' => function(\$client, array \$mocks = []) {\n";
        if ($bodyStr !== "[]") {
            $out .= "        \$response = \$client->call('$method', '$path', $bodyStr);\n";
        } else {
            $out .= "        \$response = \$client->call('$method', '$path');\n";
        }
        $out .= "        return \$response->status() === $status;\n";
        $out .= "    },\n";
    } else {
        $out = "    public function test{$opId}() {\n";
        $out .= "        \$mocks = file_exists(__DIR__ . '/../../src/mocks.php') ? require __DIR__ . '/../../src/mocks.php' : [];\n";
        if ($bodyStr !== "[]") {
            $out .= "        \$response = \$this->call('$method', '$path', $bodyStr);\n";
        } else {
            $out .= "        \$response = \$this->call('$method', '$path');\n";
        }
        $out .= "        \$this->assertEquals($status, \$response->status());\n";
        $out .= "    }\n";
    }
    return $out;
}

/**
 * emit_modular
 */
function emit_modular(array $paths): array
{
    $files = [];
    foreach ($paths as $path => $methods) {
        foreach ((array)$methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $files = array_merge($files, emit_modular_single_test($addMethod, $path, $addOp));
                }
            } elseif (in_array(strtolower($method), ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'])) {
                $files = array_merge($files, emit_modular_single_test($method, $path, $operation));
            }
        }
    }
    return $files;
}

/**
 * emit_modular_single_test
 */
function emit_modular_single_test(string $method, string $path, array $operation): array
{
    $opId = $operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path);
    $className = ucfirst($opId) . 'Test';
    $code = "<?php\n\nnamespace Api\\Tests\\Routes;\n\nuse PHPUnit\\Framework\\TestCase;\n\nclass $className extends TestCase {\n";

    // figure out expected status
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
                if ($codeVal !== 'default' && !str_starts_with((string)$codeVal, 'x-')) {
                    $status = $codeVal;
                    break;
                }
            }
        }
    }

    $code .= "    protected function call(string \$method, string \$path, array \$body = []) {\n";
    $code .= "        return new class($status) { \n";
    $code .= "            private \$status;\n";
    $code .= "            public function __construct(\$status) { \$this->status = \$status; }\n";
    $code .= "            public function status() { return \$this->status; }\n";
    $code .= "        };\n";
    $code .= "    }\n\n";

    $code .= \Cdd\Tests\emit($method, $path, $operation, false);
    $code .= "}\n";
    return ["{$className}.php" => $code];
}
