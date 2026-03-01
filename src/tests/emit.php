<?php

declare(strict_types=1);

namespace Cdd\Tests;

/**
 * Emits a PHP test method.
 *
 * @param string $method
 * @param string $path
 * @param array $operation
 * @return string
 */
function emit(string $method, string $path, array $operation): string {
    if ($method === 'additionalOperations' && is_array($operation)) {
        $out = '';
        foreach ($operation as $addMethod => $addOp) {
            $out .= emit($addMethod, $path, $addOp) . "\n";
        }
        return trim($out);
    }
    
    $m = ucfirst(strtolower($method));
    $opId = $operation['operationId'] ?? "{$m}Route";
    
    $status = '200';
    if (isset($operation['responses'])) {
        foreach ($operation['responses'] as $code => $resp) {
            if ($code !== 'default' && !str_starts_with((string)$code, 'x-')) {
                $status = $code;
                break;
            }
        }
    }
    
    $out = "    public function test{$opId}() {\n";
    $out .= "        \$response = \$this->call('$method', '$path');\n";
    $out .= "        \$this->assertEquals($status, \$response->status());\n";
    $out .= "    }\n";
    return $out;
}
