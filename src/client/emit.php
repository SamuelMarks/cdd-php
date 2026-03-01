<?php

declare(strict_types=1);

namespace Cdd\Client;

/**
 * Emits PHP code for a client operation.
 *
 * @param string $method The HTTP method.
 * @param string $path The endpoint path.
 * @param array $operation The OpenAPI operation object.
 * @param string $baseUrl The base URL for the client.
 * @return string The generated PHP method code.
 */
function emit(string $method, string $path, array $operation, string $baseUrl = 'http://localhost'): string {
    $methodName = strtolower($method);
    $operationId = $operation['operationId'] ?? "{$methodName}_" . preg_replace('/[^a-zA-Z0-9]/', '_', $path);
    
    $out = "    public function $operationId(array \$params = [], array \$body = []) {\n";
    
    if (isset($operation['security'])) {
        $out .= \Cdd\Security\emit($operation['security']);
    }

    $out .= "        \$ch = curl_init();\n";
    $out .= "        \$url = \"{\$this->baseUrl}{$path}\";\n";
    
    $out .= "        if (!empty(\$params)) {\n";
    $out .= "            \$url .= '?' . http_build_query(\$params);\n";
    $out .= "        }\n";
    
    $out .= "        curl_setopt(\$ch, CURLOPT_URL, \$url);\n";
    $out .= "        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
    $out .= "        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, strtoupper('$method'));\n";
    
    $out .= "        \$headers = [];\n";
    $out .= "        if (!empty(\$body)) {\n";
    $out .= "            curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$body));\n";
    $out .= "            \$headers[] = 'Content-Type: application/json';\n";
    $out .= "        }\n";
    
    $out .= "        if (!empty(\$headers)) {\n";
    $out .= "            curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n";
    $out .= "        }\n";
    
    $out .= "        \$response = curl_exec(\$ch);\n";
    $out .= "        \$error = curl_error(\$ch);\n";
    $out .= "        curl_close(\$ch);\n";
    
    $out .= "        if (\$error) {\n";
    $out .= "            throw new \\RuntimeException('cURL Error: ' . \$error);\n";
    $out .= "        }\n";
    
    $out .= "        return json_decode(\$response, true);\n";
    $out .= "    }\n";
    
    return $out;
}

/**
 * Emits the full ApiClient class, preserving existing methods.
 *
 * @param array $paths The OpenAPI Paths Object
 * @param string $existingCode Existing PHP code
 * @return string The generated PHP Client code
 */
function emit_class(array $paths, string $existingCode = ''): string {
    if ($existingCode !== '') {
        $out = $existingCode;
    } else {
        $out = "<?php\n\nclass ApiClient {\n    private \$baseUrl;\n\n    public function __construct(string \$baseUrl) {\n        \$this->baseUrl = \$baseUrl;\n    }\n\n";
        $out .= "    protected function requireSecurity(string \$name, array \$scopes = []) {\n";
        $out .= "        // Base security requirement mock\n";
        $out .= "    }\n\n}\n";
    }

    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $m = strtolower($addMethod);
                    $operationId = $addOp['operationId'] ?? "{$m}_" . preg_replace('/[^a-zA-Z0-9]/', '_', $path);
                    if (strpos($out, "function $operationId(") === false) {
                        $methodCode = emit($addMethod, $path, $addOp) . "\n";
                        $pos = strrpos($out, '}');
                        if ($pos !== false) {
                            $out = substr($out, 0, $pos) . $methodCode . "}\n";
                        }
                    }
                }
            } else {
                $methodName = strtolower($method);
                if (in_array($methodName, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
                    $operationId = $operation['operationId'] ?? "{$methodName}_" . preg_replace('/[^a-zA-Z0-9]/', '_', $path);
                    if (strpos($out, "function $operationId(") === false) {
                        $methodCode = emit($method, $path, $operation) . "\n";
                        $pos = strrpos($out, '}');
                        if ($pos !== false) {
                            $out = substr($out, 0, $pos) . $methodCode . "}\n";
                        }
                    }
                }
            }
        }
    }
    
    return $out;
}
