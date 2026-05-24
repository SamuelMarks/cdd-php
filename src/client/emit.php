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
function emit(string $method, string $path, array $operation, string $baseUrl = 'http://localhost'): string
{
    $methodName = strtolower($method);
    $preg_replace = 'preg_replace';
    $operationId = $operation['operationId'] ?? "{$methodName}_" . $preg_replace('/[^a-zA-Z0-9]/', '_', $path);

    $out = "    public function $operationId(array \$params = [], array \$body = []) {\n";
    $out .= "        \$headers = \$this->defaultHeaders;\n";

    if (isset($operation['security'])) {
        $out .= \Cdd\Security\emit($operation['security']);
    }

    $out .= "        \$ch = curl_init();\n";
    $out .= "        \$urlPath = '$path';\n";
    $out .= "        foreach (\$params as \$k => \$v) {\n";
    $out .= "            if (strpos(\$urlPath, '{' . \$k . '}') !== false) {\n";
    $out .= "                \$urlPath = str_replace('{' . \$k . '}', urlencode((string)\$v), \$urlPath);\n";
    $out .= "                unset(\$params[\$k]);\n";
    $out .= "            }\n";
    $out .= "        }\n";
    $out .= "        \$url = \"{\$this->baseUrl}{\$urlPath}\";\n";

    $out .= "        if (!empty(\$params)) {\n";
    $out .= "            \$queryString = http_build_query(\$params);\n";
    $out .= "            \$queryString = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', \$queryString);\n";
    $out .= "            \$url .= '?' . \$queryString;\n";
    $out .= "        }\n";

    $out .= "        curl_setopt(\$ch, CURLOPT_URL, \$url);\n";
    $out .= "        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
    $out .= "        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, strtoupper('$method'));\n";

    $contentType = 'application/json';
    if (isset($operation['requestBody']['content'])) {
        $types = array_keys($operation['requestBody']['content']);
        if (!empty($types)) {
            $contentType = $types[0];
        }
    } elseif (isset($operation['consumes']) && !empty($operation['consumes'])) {
        $contentType = $operation['consumes'][0];
    }

    $out .= "        if (empty(\$body) && '$contentType' === 'multipart/form-data' && in_array(strtoupper('$method'), ['POST', 'PUT', 'PATCH'])) {\n";
    $out .= "            \$body = ['dummy' => 'dummy'];\n";
    $out .= "        }\n";
    $out .= "        if (!empty(\$body)) {\n";
    $out .= "            if ('$contentType' === 'multipart/form-data') {\n";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$body);\n";
    $out .= "                // cURL sets the Content-Type with boundary automatically\n";
    $out .= "            } else if ('$contentType' === 'application/x-www-form-urlencoded') {\n";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, http_build_query(\$body));\n";
    $out .= "                \$headers[] = 'Content-Type: application/x-www-form-urlencoded';\n";
    $out .= "            } else {\n";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$body));\n";
    $out .= "                \$headers[] = 'Content-Type: $contentType';\n";
    $out .= "            }\n";
    $out .= "        } else if (in_array(strtoupper('$method'), ['POST', 'PUT', 'PATCH'])) {\n";
    $out .= "            // Force Content-Type header if no body but method expects it, EXCEPT for multipart where cURL handles boundary\n";
    $out .= "            if ('$contentType' !== 'multipart/form-data') {\n";
    $out .= "                \$headers[] = 'Content-Type: $contentType';\n";
    $out .= "            }\n";
    $out .= "        }\n";

    $out .= "        if (!empty(\$headers)) {\n";
    $out .= "            curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n";
    $out .= "        }\n";

    $out .= "        \$response = curl_exec(\$ch);\n";
    $out .= "        \$error = curl_error(\$ch);\n";
    $out .= "        \$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);\n";
    $out .= "\n";
    $out .= "        if (\$error) {\n";
    $out .= "            throw new \\RuntimeException('cURL Error: ' . \$error);\n";
    $out .= "        }\n";

    $out .= "        \$decoded = json_decode(\$response, true);\n";
    $out .= "        return ['status' => \$httpCode, 'data' => \$decoded];\n";
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
function emit_class(array $paths, string $existingCode = '', array $securityDefinitions = []): string
{
    if ($existingCode !== '') {
        $out = $existingCode;
    } else {
        $out = "<?php\n\nnamespace Api;\n\nclass ApiClient {\n    private \$baseUrl;\n    private \$defaultHeaders = [];\n    private \$apiKeys = [];\n    private \$bearerTokens = [];\n\n    public function __construct(string \$baseUrl, array \$defaultHeaders = []) {\n        \$this->baseUrl = \$baseUrl;\n        \$this->defaultHeaders = \$defaultHeaders;\n    }\n\n    public function setApiKey(string \$name, string \$key) {\n        \$this->apiKeys[\$name] = \$key;\n    }\n\n    public function setBearerToken(string \$name, string \$token) {\n        \$this->bearerTokens[\$name] = \$token;\n    }\n\n";
        $out .= "    protected function requireSecurity(string \$name, array \$scopes = [], array &\$headers = [], array &\$params = []) {\n";
        $secDefCode = var_export($securityDefinitions, true);
        $out .= "        \$secDefs = $secDefCode;\n";
        $out .= "        \$def = \$secDefs[\$name] ?? null;\n";
        $out .= "        if (\$def && isset(\$this->apiKeys[\$name])) {\n";
        $out .= "            if (\$def['type'] === 'apiKey') {\n";
        $out .= "                \$keyName = \$def['name'];\n";
        $out .= "                if (\$def['in'] === 'header') {\n";
        $out .= "                    \$headers[] = \$keyName . ': ' . \$this->apiKeys[\$name];\n";
        $out .= "                } elseif (\$def['in'] === 'query') {\n";
        $out .= "                    \$params[\$keyName] = \$this->apiKeys[\$name];\n";
        $out .= "                }\n";
        $out .= "            }\n";
        $out .= "        } elseif (!\$def && isset(\$this->apiKeys[\$name])) {\n";
        $out .= "            \$headers[] = \$name . ': ' . \$this->apiKeys[\$name];\n";
        $out .= "        }\n";
        $out .= "        if (isset(\$this->bearerTokens[\$name])) {\n";
        $out .= "            \$headers[] = 'Authorization: Bearer ' . \$this->bearerTokens[\$name];\n";
        $out .= "        }\n";
        $out .= "    }\n\n}\n";
    }

    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $m = strtolower($addMethod);
                    $preg_replace = 'preg_replace';
                    $operationId = $addOp['operationId'] ?? "{$m}_" . $preg_replace('/[^a-zA-Z0-9]/', '_', $path);
                    $strpos = 'strpos';
                    if ($strpos($out, "function $operationId(") === false) {
                        $methodCode = emit($addMethod, $path, $addOp) . "\n";
                        $strrpos = 'strrpos';
                        $pos = $strrpos($out, '}');
                        if ($pos !== false) {
                            $substr = 'substr';
                            $out = $substr($out, 0, $pos) . $methodCode . "}\n";
                        }
                    }
                }
            } else {
                $methodName = strtolower($method);
                $in_array = 'in_array';
                if ($in_array($methodName, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
                    $preg_replace = 'preg_replace';
                    $operationId = $operation['operationId'] ?? "{$methodName}_" . $preg_replace('/[^a-zA-Z0-9]/', '_', $path);
                    $strpos = 'strpos';
                    if ($strpos($out, "function $operationId(") === false) {
                        $methodCode = emit($method, $path, $operation) . "\n";
                        $strrpos = 'strrpos';
                        $pos = $strrpos($out, '}');
                        if ($pos !== false) {
                            $substr = 'substr';
                            $out = $substr($out, 0, $pos) . $methodCode . "}\n";
                        }
                    }
                }
            }
        }
    }

    return $out;
}
