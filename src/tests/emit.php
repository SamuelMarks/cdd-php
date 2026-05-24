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
        $str_starts_with = 'str_starts_with';
        foreach ($operation['responses'] as $code => $resp) {
            if ($code !== 'default' && !$str_starts_with((string)$code, 'x-')) {
                $status = $code;
                break;
            }
        }
    }

    if ($composable) {
        $out = "    '{$opId}' => function(\$client, array \$mocks = []) {\n";
        $out .= "        \$response = \$client->call('$method', '$path');\n";
        $out .= "        return \$response->status() === $status;\n";
        $out .= "    },\n";
    } else {
        $out = "    public function test{$opId}() {\n";
        $out .= "        \$response = \$this->call('$method', '$path');\n";
        $out .= "        \$this->assertEquals($status, \$response->status());\n";
        $out .= "    }\n";
    }
    return $out;
}
