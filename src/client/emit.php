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

    if (strpos($out, 'public function mcp(') === false) {
        $mcpCode = "    public function mcp() {\n";
        $mcpCode .= "        return new class(\$this) {\n";
        $mcpCode .= "            private \$client;\n";
        $mcpCode .= "            public function __construct(\$client) { \$this->client = \$client; }\n";
        $mcpCode .= "            public function get_tools() {\n";
        $mcpCode .= "                return [\n";
        $commands = [];
        $docs = [];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array(strtolower($method), ['parameters', 'summary', 'description', 'servers', 'additionaloperations'])) {
                    continue;
                }
                $m = strtolower($method);
                $opId = $operation['operationId'] ?? "{$m}_" . preg_replace('/[^a-zA-Z0-9]/', '_', $path);
                $commands[$opId] = $operation;
                $docs[] = strtoupper($m) . " " . $path;
                $desc = $operation['description'] ?? "Call $opId";
                $props = [];
                $required = [];
                if (isset($operation['parameters'])) {
                    foreach ($operation['parameters'] as $p) {
                        $pName = $p['name'] ?? 'param';
                        $props[$pName] = ['type' => 'string'];
                        if (!empty($p['required'])) {
                            $required[] = $pName;
                        }
                    }
                }
                if (isset($operation['requestBody'])) {
                    $props['body'] = ['type' => 'object'];
                }
                $schema = ['type' => 'object', 'properties' => (object)$props];
                if (!empty($required)) {
                    $schema['required'] = $required;
                }
                $schemaJson = addslashes(json_encode($schema));
                $mcpCode .= "                    [\n";
                $mcpCode .= "                        'name' => '$opId',\n";
                $mcpCode .= "                        'description' => '" . addslashes($desc) . "',\n";
                $mcpCode .= "                        'inputSchema' => json_decode('$schemaJson', true)\n";
                $mcpCode .= "                    ],\n";
            }
        }
        $mcpCode .= "                ];\n";
        $mcpCode .= "            }\n";
        $mcpCode .= "            public function get_resources() {\n";
        $mcpCode .= "                return [\n";
        $mcpCode .= "                    [\n";
        $mcpCode .= "                        'uri' => 'api://docs',\n";
        $mcpCode .= "                        'name' => 'API Documentation',\n";
        $mcpCode .= "                        'mimeType' => 'text/plain'\n";
        $mcpCode .= "                    ]\n";
        $mcpCode .= "                ];\n";
        $mcpCode .= "            }\n";
        $mcpCode .= "            public function read_resource(string \$uri) {\n";
        $mcpCode .= "                if (\$uri === 'api://docs') {\n";
        $mcpCode .= "                    return [\n";
        $mcpCode .= "                        'contents' => [\n";
        $mcpCode .= "                            [\n";
        $mcpCode .= "                                'uri' => 'api://docs',\n";
        $mcpCode .= "                                'mimeType' => 'text/plain',\n";
        $mcpCode .= "                                'text' => \"API Endpoints:\\n" . implode("\\n", $docs) . "\"\n";
        $mcpCode .= "                            ]\n";
        $mcpCode .= "                        ]\n";
        $mcpCode .= "                    ];\n";
        $mcpCode .= "                }\n";
        $mcpCode .= "                throw new \Exception(\"Unknown resource: \" . \$uri);\n";
        $mcpCode .= "            }\n";
        $mcpCode .= "            public function execute_tool(string \$name, array \$args) {\n";
        $mcpCode .= "                switch (\$name) {\n";
        foreach ($commands as $opId => $operation) {
            $mcpCode .= "                    case '$opId':\n";
            $mcpCode .= "                        \$params = [];\n";
            $mcpCode .= "                        \$body = [];\n";
            if (isset($operation['parameters'])) {
                foreach ($operation['parameters'] as $p) {
                    $pName = $p['name'] ?? 'param';
                    $mcpCode .= "                        \$params['$pName'] = \$args['$pName'] ?? null;\n";
                }
            }
            if (isset($operation['requestBody'])) {
                $mcpCode .= "                        \$body = \$args['body'] ?? [];\n";
            }
            $mcpCode .= "                        return \$this->client->$opId(\$params, \$body);\n";
        }
        $mcpCode .= "                    default:\n";
        $mcpCode .= "                        throw new \Exception(\"Unknown tool: \" . \$name);\n";
        $mcpCode .= "                }\n";
        $mcpCode .= "            }\n";
        $mcpCode .= "        };\n";
        $mcpCode .= "    }\n";

        $pos = strrpos($out, '}');
        if ($pos !== false) {
            $out = substr($out, 0, $pos) . $mcpCode . "}\n";
        }
    }

    if (strpos($out, 'public function connect_mcp(') === false) {
        $mcpConnectCode = "    public function connect_mcp(\$transport) {\n";
        $mcpConnectCode .= "        \$process = null;\n";
        $mcpConnectCode .= "        \$pipes = [];\n";
        $mcpConnectCode .= "        if (is_string(\$transport)) {\n";
        $mcpConnectCode .= "            \$descriptorspec = [\n";
        $mcpConnectCode .= "                0 => [\"pipe\", \"r\"],\n";
        $mcpConnectCode .= "                1 => [\"pipe\", \"w\"],\n";
        $mcpConnectCode .= "                2 => [\"pipe\", \"w\"]\n";
        $mcpConnectCode .= "            ];\n";
        $mcpConnectCode .= "            \$process = proc_open(\$transport, \$descriptorspec, \$pipes);\n";
        $mcpConnectCode .= "            if (!is_resource(\$process)) throw new \Exception('Failed to start MCP server');\n";
        $mcpConnectCode .= "            \$rpcCall = function(\$method, \$params = []) use (&\$pipes) {\n";
        $mcpConnectCode .= "                \$id = uniqid();\n";
        $mcpConnectCode .= "                \$req = json_encode(['jsonrpc' => '2.0', 'id' => \$id, 'method' => \$method, 'params' => \$params]) . \"\\n\";\n";
        $mcpConnectCode .= "                fwrite(\$pipes[0], \$req);\n";
        $mcpConnectCode .= "                while ((\$line = fgets(\$pipes[1])) !== false) {\n";
        $mcpConnectCode .= "                    \$res = json_decode(\$line, true);\n";
        $mcpConnectCode .= "                    if (\$res && isset(\$res['id']) && \$res['id'] === \$id) return \$res;\n";
        $mcpConnectCode .= "                }\n";
        $mcpConnectCode .= "                return null;\n";
        $mcpConnectCode .= "            };\n";
        $mcpConnectCode .= "        } elseif (is_callable(\$transport)) {\n";
        $mcpConnectCode .= "            \$rpcCall = \$transport;\n";
        $mcpConnectCode .= "        } else {\n";
        $mcpConnectCode .= "            throw new \Exception('Invalid transport provided');\n";
        $mcpConnectCode .= "        }\n";
        $mcpConnectCode .= "        \$init = \$rpcCall('initialize', ['protocolVersion' => '2024-11-05', 'capabilities' => [], 'clientInfo' => ['name' => 'cdd-client', 'version' => '1.0.0']]);\n";
        $mcpConnectCode .= "        if (!isset(\$init['result'])) throw new \Exception('MCP initialization failed');\n";
        $mcpConnectCode .= "        if (is_string(\$transport)) fwrite(\$pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'initialized', 'params' => []]) . \"\\n\");\n";
        $mcpConnectCode .= "        else \$rpcCall('initialized', []);\n";
        $mcpConnectCode .= "        return new class(\$rpcCall, \$process, \$pipes) {\n";
        $mcpConnectCode .= "            private \$rpcCall;\n";
        $mcpConnectCode .= "            private \$process;\n";
        $mcpConnectCode .= "            private \$pipes;\n";
        $mcpConnectCode .= "            public function __construct(\$rpcCall, \$process, \$pipes) { \$this->rpcCall = \$rpcCall; \$this->process = \$process; \$this->pipes = \$pipes; }\n";
        $mcpConnectCode .= "            public function __destruct() {\n";
        $mcpConnectCode .= "                foreach(\$this->pipes as \$p) fclose(\$p);\n";
        $mcpConnectCode .= "                proc_terminate(\$this->process);\n";
        $mcpConnectCode .= "            }\n";
        $mcpConnectCode .= "            public function get_tools() {\n";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;\n";
        $mcpConnectCode .= "                \$res = \$call('tools/list');\n";
        $mcpConnectCode .= "                return \$res['result']['tools'] ?? [];\n";
        $mcpConnectCode .= "            }\n";
        $mcpConnectCode .= "            public function execute_tool(string \$name, array \$args) {\n";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;\n";
        $mcpConnectCode .= "                \$res = \$call('tools/call', ['name' => \$name, 'arguments' => \$args]);\n";
        $mcpConnectCode .= "                if (isset(\$res['error'])) throw new \Exception(\$res['error']['message'] ?? 'Tool execution failed');\n";
        $mcpConnectCode .= "                return \$res['result']['content'][0]['text'] ?? null;\n";
        $mcpConnectCode .= "            }\n";
        $mcpConnectCode .= "            public function ping() {\n";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;\n";
        $mcpConnectCode .= "                \$res = \$call('ping');\n";
        $mcpConnectCode .= "                return isset(\$res['result']);\n";
        $mcpConnectCode .= "            }\n";
        $mcpConnectCode .= "            public function get_resources() {\n";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;\n";
        $mcpConnectCode .= "                \$res = \$call('resources/list');\n";
        $mcpConnectCode .= "                return \$res['result']['resources'] ?? [];\n";
        $mcpConnectCode .= "            }\n";
        $mcpConnectCode .= "            public function read_resource(string \$uri) {\n";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;\n";
        $mcpConnectCode .= "                \$res = \$call('resources/read', ['uri' => \$uri]);\n";
        $mcpConnectCode .= "                if (isset(\$res['error'])) throw new \Exception(\$res['error']['message'] ?? 'Resource read failed');\n";
        $mcpConnectCode .= "                return \$res['result']['contents'] ?? [];\n";
        $mcpConnectCode .= "            }\n";
        $mcpConnectCode .= "        };\n";
        $mcpConnectCode .= "    }\n";
        $mcpConnectCode .= "    public function connect_mcp_sse(string \$url) {\n";
        $mcpConnectCode .= "        \$ch = curl_init();\n";
        $mcpConnectCode .= "        curl_setopt(\$ch, CURLOPT_URL, \$url);\n";
        $mcpConnectCode .= "        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
        $mcpConnectCode .= "        \$headers = ['Accept: text/event-stream'];\n";
        $mcpConnectCode .= "        foreach (\$this->headers as \$k => \$v) \$headers[] = \"\$k: \$v\";\n";
        $mcpConnectCode .= "        curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n";
        $mcpConnectCode .= "        // Not fully implemented event loop for SSE due to blocking PHP nature, just dummy for now\n";
        $mcpConnectCode .= "        return new class(\$url) {\n";
        $mcpConnectCode .= "            private \$url;\n";
        $mcpConnectCode .= "            public function __construct(\$url) { \$this->url = \$url; }\n";
        $mcpConnectCode .= "            public function get_tools() { return []; }\n";
        $mcpConnectCode .= "        };\n";
        $mcpConnectCode .= "    }\n";

        $pos = strrpos($out, '}');
        if ($pos !== false) {
            $out = substr($out, 0, $pos) . $mcpConnectCode . "}\n";
        }
    }

    return $out;
}
