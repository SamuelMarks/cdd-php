<?php

/**
 * Emits PHP client SDK methods and the full ApiClient class.
 *
 * @package Cdd\Client
 */

declare(strict_types=1);

namespace Cdd\Client;

/**
 * Emits PHP code for a client operation.
 *
 * @param string $method The HTTP method.
 * @param string $path The endpoint path.
 * @param array<string, mixed> $operation The OpenAPI operation object.
 * @param string $baseUrl The base URL for the client.
 * @return string The generated PHP method code.
 */
function emit(string $method, string $path, array $operation, string $baseUrl = 'http://localhost'): string
{
    $methodName = strtolower($method);
    $rawOpId = (string) ($operation['operationId'] ?? "{$methodName}_" . preg_replace('/[^a-zA-Z0-9]/', '_', $path));
    $operationId = \Cdd\Parameters\sanitize_var_name($rawOpId);
    $exportedPath = var_export($path, true);
    $exportedMethod = var_export($method, true);

    $docLines = [];
    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
        foreach ($operation['parameters'] as $paramItem) {
            if (is_array($paramItem) && (isset($paramItem['collectionFormat']) || isset($paramItem['x-collectionFormat']) || isset($paramItem['style']) || isset($paramItem['schema']['items']['collectionFormat']))) {
                $docLines[] = '     * @oas-param ' . json_encode($paramItem);
            }
        }
    }
    if (isset($operation['responses']) && is_array($operation['responses'])) {
        foreach ($operation['responses'] as $respCode => $respItem) {
            if (is_array($respItem) && isset($respItem['headers']) && is_array($respItem['headers'])) {
                foreach ($respItem['headers'] as $hName => $hObj) {
                    $docLines[] = "     * @oas-header {$respCode} {$hName} " . json_encode($hObj);
                }
            }
        }
    }

    $out = '';
    if (!empty($docLines)) {
        $out .= "    /**\n" . implode("\n", $docLines) . "\n     */\n";
    }
    $out .= "    public function $operationId(array \$params = [], array \$body = []) {\n";
    $out .= "        \$headers = \$this->defaultHeaders;\n";
    $out .= "        if (!empty(\$this->sessionToken)) {\n";
    $out .= "            \$headers[] = 'Authorization: Bearer ' . \$this->sessionToken;\n";
    $out .= "        }\n";

    if (isset($operation['security'])) {
        $out .= \Cdd\Security\emit($operation['security']);
    }

    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
        $sepMap = ['csv' => ',', 'ssv' => ' ', 'tsv' => "\t", 'pipes' => '|'];
        foreach ($operation['parameters'] as $paramItem) {
            $pName = (string) ($paramItem['name'] ?? 'param');
            $exportedPName = var_export($pName, true);
            $pCf = (string) ($paramItem['x-collectionFormat'] ?? $paramItem['collectionFormat'] ?? '');
            $itemsCf = (string) ($paramItem['schema']['items']['collectionFormat'] ?? $paramItem['items']['collectionFormat'] ?? '');
            if (isset($sepMap[$itemsCf])) {
                $exportedItemSep = var_export($sepMap[$itemsCf], true);
                $out .= "        if (isset(\$params[{$exportedPName}]) && is_array(\$params[{$exportedPName}])) {\n";
                $out .= "            \$params[{$exportedPName}] = array_map(fn(\$item) => is_array(\$item) ? implode({$exportedItemSep}, \$item) : \$item, \$params[{$exportedPName}]);\n";
                $out .= "        }\n";
            }
            if (isset($sepMap[$pCf])) {
                $exportedSep = var_export($sepMap[$pCf], true);
                $out .= "        if (isset(\$params[{$exportedPName}]) && is_array(\$params[{$exportedPName}])) {\n";
                $out .= "            \$params[{$exportedPName}] = implode({$exportedSep}, \$params[{$exportedPName}]);\n";
                $out .= "        }\n";
            }
        }
    }

    $out .= "        \$ch = curl_init();
";
    $out .= "        \$urlPath = {$exportedPath};
";
    $out .= "        foreach (\$params as \$k => \$v) {
";
    $out .= "            if (strpos(\$urlPath, '{' . \$k . '}') !== false) {
";
    $out .= "                \$urlPath = str_replace('{' . \$k . '}', urlencode((string)\$v), \$urlPath);
";
    $out .= "                unset(\$params[\$k]);
";
    $out .= "            }
";
    $out .= "        }\n";
    $out .= "        \$url = \$this->baseUrl . \$urlPath;\n";

    $out .= "        if (!empty(\$params)) {
";
    $out .= "            \$queryString = http_build_query(\$params);
";
    $out .= "            \$queryString = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', \$queryString);
";
    $out .= "            \$url .= '?' . \$queryString;
";
    $out .= "        }
";

    $out .= "        curl_setopt(\$ch, CURLOPT_URL, \$url);
";
    $out .= "        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
";
    $out .= "        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, strtoupper({$exportedMethod}));
";

    $contentType = 'application/json';
    if (isset($operation['requestBody']['content'])) {
        $types = array_keys($operation['requestBody']['content']);
        if (!empty($types)) {
            $contentType = (string) $types[0];
        }
    } elseif (isset($operation['consumes']) && !empty($operation['consumes'])) {
        $contentType = (string) $operation['consumes'][0];
    }
    $exportedContentType = var_export($contentType, true);
    $exportedContentTypeHeader = var_export("Content-Type: {$contentType}", true);

    $out .= "        if (empty(\$body) && {$exportedContentType} === 'multipart/form-data' && in_array(strtoupper({$exportedMethod}), ['POST', 'PUT', 'PATCH'])) {
";
    $out .= "            \$body = ['dummy' => 'dummy'];
";
    $out .= "        }
";
    $out .= "        if (!empty(\$body)) {
";
    $out .= "            if ({$exportedContentType} === 'multipart/form-data') {
";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$body);
";
    $out .= "                \$headers[] = 'Expect:';
";
    $out .= "                // cURL sets the Content-Type with boundary automatically
";
    $out .= "            } else if ({$exportedContentType} === 'application/x-www-form-urlencoded') {
";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, http_build_query(\$body));
";
    $out .= "                \$headers[] = 'Content-Type: application/x-www-form-urlencoded';
";
    $out .= "            } else {
";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$body));
";
    $out .= "                \$headers[] = {$exportedContentTypeHeader};
";
    $out .= "            }
";
    $out .= "        } else if (in_array(strtoupper({$exportedMethod}), ['POST', 'PUT', 'PATCH'])) {
";
    $out .= "            // Force Content-Type header if no body but method expects it, EXCEPT for multipart where cURL handles boundary
";
    $out .= "            if ({$exportedContentType} !== 'multipart/form-data') {
";
    $out .= "                curl_setopt(\$ch, CURLOPT_POSTFIELDS, '');
";
    $out .= "                \$headers[] = {$exportedContentTypeHeader};
";
    $out .= "            }
";
    $out .= "        }
";

    $out .= "        if (!empty(\$headers)) {
";
    $out .= "            curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
";
    $out .= "        }
";
    $out .= "        if (is_callable(\$this->customTransport)) {
";
    $out .= "            return (\$this->customTransport)(strtoupper({$exportedMethod}), \$url, \$headers, \$body);
";
    $out .= "        }
";

    $out .= "        \$response = curl_exec(\$ch);
";
    $out .= "        \$error = curl_error(\$ch);
";
    $out .= "        \$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
";
    $out .= "
";
    $out .= "        if (\$response === false || \$error !== '') {
";
    $out .= "            throw new \RuntimeException('cURL Error: ' . \$error);
";
    $out .= "        }
";

    $out .= "        \$decoded = json_decode((string) \$response, true);
";
    $out .= "        return ['status' => \$httpCode, 'data' => \$decoded];
";
    $out .= "    }
";

    return $out;
}

/**
 * Emits the full ApiClient class, preserving existing methods.
 *
 * @param array<string, mixed> $paths The OpenAPI Paths Object
 * @param string $existingCode Existing PHP code
 * @param array<string, mixed> $securityDefinitions Security Scheme definitions map
 * @return string The generated PHP Client code
 */
function emit_class(array $paths, string $existingCode = '', array $securityDefinitions = []): string
{
    if ($existingCode !== '') {
        $out = $existingCode;
    } else {
        $out = "<?php

namespace Api;

class ApiClient {
    private \$baseUrl;
    private \$defaultHeaders = [];
    private \$apiKeys = [];
    private \$bearerTokens = [];
    private ?string \$sessionToken = null;
    private ?string \$refreshToken = null;
    private \$customTransport = null;
    public object \$auth;

    public function __construct(string \$baseUrl, array \$defaultHeaders = []) {
        \$this->baseUrl = \$baseUrl;
        \$this->defaultHeaders = \$defaultHeaders;
        \$this->auth = \$this->createAuthHelper();
    }

    public function setApiKey(string \$name, string \$key) {
        \$this->apiKeys[\$name] = \$key;
    }

    public function setBearerToken(string \$name, string \$token) {
        \$this->bearerTokens[\$name] = \$token;
    }

    public function setSessionToken(?string \$token, ?string \$refreshToken = null): void {
        \$this->sessionToken = \$token;
        if (\$refreshToken !== null) {
            \$this->refreshToken = \$refreshToken;
        }
    }

    public function getSessionToken(): ?string {
        return \$this->sessionToken;
    }

    public function getRefreshToken(): ?string {
        return \$this->refreshToken;
    }

    public function setTransport(?callable \$transport): void {
        \$this->customTransport = \$transport;
    }

    protected function callAuthEndpoint(string \$endpoint, array \$payload = []): array {
        \$headers = \$this->defaultHeaders;
        \$headers[] = 'Content-Type: application/json';
        if (!empty(\$this->sessionToken)) {
            \$headers[] = 'Authorization: Bearer ' . \$this->sessionToken;
        }
        \$url = rtrim(\$this->baseUrl, '/') . \$endpoint;
        if (is_callable(\$this->customTransport)) {
            return (\$this->customTransport)('POST', \$url, \$headers, \$payload);
        }
        \$ch = curl_init(\$url);
        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt(\$ch, CURLOPT_POSTFIELDS, (string) json_encode(\$payload));
        curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
        \$raw = curl_exec(\$ch);
        \$code = (int) curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
        \$data = is_string(\$raw) ? json_decode(\$raw, true) : null;
        return ['status' => \$code, 'data' => is_array(\$data) ? \$data : []];
    }

    protected function createAuthHelper(): object {
        \$self = \$this;
        \$callEndpoint = function(string \$endpoint, array \$payload = []) use (\$self): array {
            return \$self->callAuthEndpoint(\$endpoint, \$payload);
        };
        return new class(\$self, \$callEndpoint) {
            private ApiClient \$client;
            private \$caller;
            public function __construct(ApiClient \$client, callable \$caller) {
                \$this->client = \$client;
                \$this->caller = \$caller;
            }
            public function register(string \$username, string \$password, array \$extra = []): array {
                \$payload = array_merge(\$extra, ['username' => \$username, 'password' => \$password]);
                \$res = (\$this->caller)('/auth/register', \$payload);
                if (isset(\$res['data']['access_token'])) {
                    \$this->client->setSessionToken((string) \$res['data']['access_token'], isset(\$res['data']['refresh_token']) ? (string) \$res['data']['refresh_token'] : null);
                }
                return \$res;
            }
            public function login(string \$username, string \$password): array {
                \$res = (\$this->caller)('/auth/login', ['username' => \$username, 'password' => \$password]);
                if (isset(\$res['data']['access_token'])) {
                    \$this->client->setSessionToken((string) \$res['data']['access_token'], isset(\$res['data']['refresh_token']) ? (string) \$res['data']['refresh_token'] : null);
                }
                return \$res;
            }
            public function refresh(): array {
                \$rt = \$this->client->getRefreshToken() ?? '';
                \$res = (\$this->caller)('/auth/refresh', ['refresh_token' => \$rt]);
                if (isset(\$res['data']['access_token'])) {
                    \$this->client->setSessionToken((string) \$res['data']['access_token'], isset(\$res['data']['refresh_token']) ? (string) \$res['data']['refresh_token'] : null);
                }
                return \$res;
            }
            public function logout(): array {
                \$res = (\$this->caller)('/auth/logout', []);
                \$this->client->setSessionToken(null);
                return \$res;
            }
        };
    }

";
        $out .= "    protected function requireSecurity(string \$name, array \$scopes = [], array &\$headers = [], array &\$params = []) {
";
        $secDefCode = var_export($securityDefinitions, true);
        $out .= "        \$secDefs = $secDefCode;
";
        $out .= "        \$def = \$secDefs[\$name] ?? null;
";
        $out .= "        if (\$def && isset(\$this->apiKeys[\$name])) {
";
        $out .= "            if (\$def['type'] === 'apiKey') {
";
        $out .= "                \$keyName = \$def['name'];
";
        $out .= "                if (\$def['in'] === 'header') {
";
        $out .= "                    \$headers[] = \$keyName . ': ' . \$this->apiKeys[\$name];
";
        $out .= "                } elseif (\$def['in'] === 'query') {
";
        $out .= "                    \$params[\$keyName] = \$this->apiKeys[\$name];
";
        $out .= "                }
";
        $out .= "            }
";
        $out .= "        } elseif (!\$def && isset(\$this->apiKeys[\$name])) {
";
        $out .= "            \$headers[] = \$name . ': ' . \$this->apiKeys[\$name];
";
        $out .= "        }
";
        $out .= "        if (isset(\$this->bearerTokens[\$name])) {
";
        $out .= "            \$headers[] = 'Authorization: Bearer ' . \$this->bearerTokens[\$name];
";
        $out .= "        }
";
        $out .= "    }

}
";
    }

    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if ($method === 'additionalOperations' && is_array($operation)) {
                foreach ($operation as $addMethod => $addOp) {
                    $m = strtolower((string) $addMethod);
                    $operationId = \Cdd\Parameters\sanitize_var_name((string) ($addOp['operationId'] ?? "{$m}_" . preg_replace('/[^a-zA-Z0-9]/', '_', (string) $path)));
                    if (strpos($out, "function $operationId(") === false) {
                        $methodCode = emit((string) $addMethod, (string) $path, $addOp) . "
";
                        $out = \Cdd\Paths\append_method_to_class($out, $methodCode);
                    }
                }
            } else {
                $methodName = strtolower((string) $method);
                if (in_array($methodName, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'])) {
                    $operationId = \Cdd\Parameters\sanitize_var_name((string) ($operation['operationId'] ?? "{$methodName}_" . preg_replace('/[^a-zA-Z0-9]/', '_', (string) $path)));
                    if (strpos($out, "function $operationId(") === false) {
                        $methodCode = emit((string) $method, (string) $path, $operation) . "
";
                        $out = \Cdd\Paths\append_method_to_class($out, $methodCode);
                    }
                }
            }
        }
    }

    if (strpos($out, 'public function mcp(') === false) {
        $mcpCode = "    public function mcp() {
";
        $mcpCode .= "        return new class(\$this) {
";
        $mcpCode .= "            private \$client;
";
        $mcpCode .= "            public function __construct(\$client) { \$this->client = \$client; }
";
        $mcpCode .= "            public function get_tools() {
";
        $mcpCode .= "                return [
";
        $commands = [];
        $docs = [];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers', 'additionaloperations'])) {
                    continue;
                }
                $m = strtolower((string) $method);
                $opId = \Cdd\Parameters\sanitize_var_name((string) ($operation['operationId'] ?? "{$m}_" . preg_replace('/[^a-zA-Z0-9]/', '_', (string) $path)));
                $commands[$opId] = $operation;
                $docs[] = strtoupper($m) . " " . $path;
                $desc = (string) ($operation['description'] ?? "Call $opId");
                $props = [];
                $required = [];
                if (isset($operation['parameters'])) {
                    foreach ($operation['parameters'] as $p) {
                        $pName = (string) ($p['name'] ?? 'param');
                        $props[$pName] = ['type' => 'string'];
                        if (!empty($p['required'])) {
                            $required[] = $pName;
                        }
                    }
                }
                if (isset($operation['requestBody'])) {
                    $props['body'] = ['type' => 'object'];
                }
                $schema = ['type' => 'object', 'properties' => (object) $props];
                if (!empty($required)) {
                    $schema['required'] = $required;
                }

                $schemaJson = var_export((string) json_encode($schema), true);
                $exportedOpId = var_export($opId, true);
                $exportedDesc = var_export($desc, true);
                $mcpCode .= "                    [
";
                $mcpCode .= "                        'name' => {$exportedOpId},
";
                $mcpCode .= "                        'description' => {$exportedDesc},
";
                $mcpCode .= "                        'inputSchema' => json_decode({$schemaJson}, true)
";
                $mcpCode .= "                    ],
";
            }
        }
        $mcpCode .= "                ];
";
        $mcpCode .= "            }
";
        $mcpCode .= "            public function get_resources() {
";
        $mcpCode .= "                return [
";
        $mcpCode .= "                    [
";
        $mcpCode .= "                        'uri' => 'api://docs',
";
        $mcpCode .= "                        'name' => 'API Documentation',
";
        $mcpCode .= "                        'mimeType' => 'text/plain'
";
        $mcpCode .= "                    ]
";
        $mcpCode .= "                ];
";
        $mcpCode .= "            }
";
        $exportedDocsText = var_export("API Endpoints:
" . implode("
", $docs), true);
        $mcpCode .= "            public function read_resource(string \$uri) {
";
        $mcpCode .= "                if (\$uri === 'api://docs') {
";
        $mcpCode .= "                    return [
";
        $mcpCode .= "                        'contents' => [
";
        $mcpCode .= "                            [
";
        $mcpCode .= "                                'uri' => 'api://docs',
";
        $mcpCode .= "                                'mimeType' => 'text/plain',
";
        $mcpCode .= "                                'text' => {$exportedDocsText}
";
        $mcpCode .= "                            ]
";
        $mcpCode .= "                        ]
";
        $mcpCode .= "                    ];
";
        $mcpCode .= "                }
";
        $mcpCode .= "                throw new \\Exception('Unknown resource: ' . \$uri);
";
        $mcpCode .= "            }
";
        $mcpCode .= "            public function execute_tool(string \$name, array \$args) {
";
        $mcpCode .= "                switch (\$name) {
";
        foreach ($commands as $opId => $operation) {
            $exportedOpId = var_export($opId, true);
            $mcpCode .= "                    case {$exportedOpId}:
";
            $mcpCode .= "                        \$params = [];
";
            $mcpCode .= "                        \$body = [];
";
            if (isset($operation['parameters'])) {
                foreach ($operation['parameters'] as $p) {
                    $pName = (string) ($p['name'] ?? 'param');
                    $exportedPName = var_export($pName, true);
                    $mcpCode .= "                        \$params[{$exportedPName}] = \$args[{$exportedPName}] ?? null;
";
                }
            }
            if (isset($operation['requestBody'])) {
                $mcpCode .= "                        \$body = \$args['body'] ?? [];
";
            }
            $mcpCode .= "                        return \$this->client->$opId(\$params, \$body);
";
        }
        $mcpCode .= "                    default:
";
        $mcpCode .= "                        throw new \\Exception('Unknown tool: ' . \$name);
";
        $mcpCode .= "                }
";
        $mcpCode .= "            }
";
        $mcpCode .= "        };
";
        $mcpCode .= "    }
";

        $out = \Cdd\Paths\append_method_to_class($out, $mcpCode);
    }

    if (strpos($out, 'public function connect_mcp(') === false) {
        $mcpConnectCode = "    public function connect_mcp(\$transport) {
";
        $mcpConnectCode .= "        \$process = null;
";
        $mcpConnectCode .= "        \$pipes = [];
";
        $mcpConnectCode .= "        if (is_string(\$transport)) {
";
        $mcpConnectCode .= "            \$descriptorspec = [
";
        $mcpConnectCode .= "                0 => ['pipe', 'r'],
";
        $mcpConnectCode .= "                1 => ['pipe', 'w'],
";
        $mcpConnectCode .= "                2 => ['pipe', 'w']
";
        $mcpConnectCode .= "            ];
";
        $mcpConnectCode .= "            \$process = proc_open(\$transport, \$descriptorspec, \$pipes);
";
        $mcpConnectCode .= "            if (!is_resource(\$process)) throw new \\Exception('Failed to start MCP server');
";
        $mcpConnectCode .= "            \$rpcCall = function(\$method, \$params = [], ?callable \$onNotification = null, bool \$isNotification = false, ?callable \$onSampling = null) use (&\$pipes) {
";
        $mcpConnectCode .= "                \$id = uniqid();
";
        $mcpConnectCode .= "                if (\$onNotification) \$params['_meta'] = ['progressToken' => \$id];
";
        $mcpConnectCode .= "                \$reqData = ['jsonrpc' => '2.0', 'method' => \$method, 'params' => \$params];
";
        $mcpConnectCode .= "                if (!\$isNotification) \$reqData['id'] = \$id;
";
        $mcpConnectCode .= "                \$req = json_encode(\$reqData) . PHP_EOL;
";
        $mcpConnectCode .= "                fwrite(\$pipes[0], \$req);
";
        $mcpConnectCode .= "                if (\$isNotification) return null;
";
        $mcpConnectCode .= "                while ((\$line = fgets(\$pipes[1])) !== false) {
";
        $mcpConnectCode .= "                    \$res = json_decode(\$line, true);
";
        $mcpConnectCode .= "                    if (\$res && isset(\$res['id']) && \$res['id'] === \$id && (isset(\$res['result']) || isset(\$res['error']))) return \$res;
";
        $mcpConnectCode .= "                    if (\$res && isset(\$res['method']) && \$res['method'] === 'sampling/createMessage') {
";
        $mcpConnectCode .= "                        if (\$onSampling) {
";
        $mcpConnectCode .= "                            \$sampleRes = \$onSampling(\$res['params']);
";
        $mcpConnectCode .= "                            fwrite(\$pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => \$res['id'], 'result' => \$sampleRes]) . PHP_EOL);
";
        $mcpConnectCode .= "                        } else {
";
        $mcpConnectCode .= "                            fwrite(\$pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => \$res['id'], 'error' => ['code' => -32601, 'message' => 'Sampling not supported']]) . PHP_EOL);
";
        $mcpConnectCode .= "                        }
";
        $mcpConnectCode .= "                        continue;
";
        $mcpConnectCode .= "                    }
";
        $mcpConnectCode .= "                    if (\$res && !isset(\$res['id']) && isset(\$res['method']) && \$onNotification) \$onNotification(\$res['method'], \$res['params'] ?? []);
";
        $mcpConnectCode .= "                }
";
        $mcpConnectCode .= "                return null;
";
        $mcpConnectCode .= "            };
";
        $mcpConnectCode .= "        } elseif (is_callable(\$transport)) {
";
        $mcpConnectCode .= "            \$rpcCall = \$transport;
";
        $mcpConnectCode .= "        } else {
";
        $mcpConnectCode .= "            throw new \Exception('Invalid transport provided');
";
        $mcpConnectCode .= "        }
";
        $mcpConnectCode .= "        \$init = \$rpcCall('initialize', ['protocolVersion' => '2024-11-05', 'capabilities' => [], 'clientInfo' => ['name' => 'cdd-client', 'version' => '1.0.0']]);
";
        $mcpConnectCode .= "        if (!isset(\$init['result'])) throw new \Exception('MCP initialization failed');
";
        $mcpConnectCode .= "        \$rpcCall('initialized', [], null, true);
";
        $mcpConnectCode .= "        return new class(\$rpcCall, \$process, \$pipes) {
";
        $mcpConnectCode .= "            private \$rpcCall;
";
        $mcpConnectCode .= "            private \$process;
";
        $mcpConnectCode .= "            private \$pipes;
";
        $mcpConnectCode .= "            public function __construct(\$rpcCall, \$process, \$pipes) { \$this->rpcCall = \$rpcCall; \$this->process = \$process; \$this->pipes = \$pipes; }
";
        $mcpConnectCode .= "            public function __destruct() {
";
        $mcpConnectCode .= "                foreach(\$this->pipes as \$p) fclose(\$p);
";
        $mcpConnectCode .= "                proc_terminate(\$this->process);
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "            public function cancel_request(string \$requestId, string \$reason = '') {
";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;
";
        $mcpConnectCode .= "                \$call('notifications/cancelled', ['requestId' => \$requestId, 'reason' => \$reason], null, true);
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "            public function get_tools() {
";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;
";
        $mcpConnectCode .= "                \$tools = [];
";
        $mcpConnectCode .= "                \$cursor = null;
";
        $mcpConnectCode .= "                do {
";
        $mcpConnectCode .= "                    \$res = \$call('tools/list', \$cursor ? ['cursor' => \$cursor] : []);
";
        $mcpConnectCode .= "                    if (isset(\$res['result']['tools'])) \$tools = array_merge(\$tools, \$res['result']['tools']);
";
        $mcpConnectCode .= "                    \$cursor = \$res['result']['nextCursor'] ?? null;
";
        $mcpConnectCode .= "                } while (\$cursor);
";
        $mcpConnectCode .= "                return \$tools;
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "            public function execute_tool(string \$name, array \$args, ?callable \$onNotification = null, ?callable \$onSampling = null) {
";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;
";
        $mcpConnectCode .= "                \$res = \$call('tools/call', ['name' => \$name, 'arguments' => \$args], \$onNotification, false, \$onSampling);
";
        $mcpConnectCode .= "                if (isset(\$res['result']['isError']) && \$res['result']['isError'] === true) {
";
        $mcpConnectCode .= "                     \$msg = \$res['result']['content'][0]['text'] ?? 'Tool execution returned an error state (e.g., User Denied).';
";
        $mcpConnectCode .= "                     throw new \Exception(\$msg);
";
        $mcpConnectCode .= "                }
";
        $mcpConnectCode .= "                if (isset(\$res['error'])) throw new \Exception(\$res['error']['message'] ?? 'Tool execution failed');
";
        $mcpConnectCode .= "                return \$res['result']['content'][0]['text'] ?? null;
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "            public function ping() {
";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;
";
        $mcpConnectCode .= "                \$res = \$call('ping');
";
        $mcpConnectCode .= "                return isset(\$res['result']);
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "            public function get_resources() {
";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;
";
        $mcpConnectCode .= "                \$resources = [];
";
        $mcpConnectCode .= "                \$cursor = null;
";
        $mcpConnectCode .= "                do {
";
        $mcpConnectCode .= "                    \$res = \$call('resources/list', \$cursor ? ['cursor' => \$cursor] : []);
";
        $mcpConnectCode .= "                    if (isset(\$res['result']['resources'])) \$resources = array_merge(\$resources, \$res['result']['resources']);
";
        $mcpConnectCode .= "                    \$cursor = \$res['result']['nextCursor'] ?? null;
";
        $mcpConnectCode .= "                } while (\$cursor);
";
        $mcpConnectCode .= "                return \$resources;
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "            public function read_resource(string \$uri) {
";
        $mcpConnectCode .= "                \$call = \$this->rpcCall;
";
        $mcpConnectCode .= "                \$res = \$call('resources/read', ['uri' => \$uri]);
";
        $mcpConnectCode .= "                if (isset(\$res['error'])) throw new \Exception(\$res['error']['message'] ?? 'Resource read failed');
";
        $mcpConnectCode .= "                return \$res['result']['contents'] ?? [];
";
        $mcpConnectCode .= "            }
";
        $mcpConnectCode .= "        };
";
        $mcpConnectCode .= "    }
";
        $mcpConnectCode .= "    public function connect_mcp_sse(string \$url) {
";
        $mcpConnectCode .= "        \$ch = curl_init();
";
        $mcpConnectCode .= "        curl_setopt(\$ch, CURLOPT_URL, \$url);
";
        $mcpConnectCode .= "        curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
";
        $mcpConnectCode .= "        \$headers = ['Accept: text/event-stream'];
";
        $mcpConnectCode .= "        foreach (\$this->defaultHeaders as \$h) \$headers[] = \$h;
";
        $mcpConnectCode .= "        foreach (\$this->bearerTokens as \$token) \$headers[] = 'Authorization: Bearer ' . \$token;
";
        $mcpConnectCode .= "        foreach (\$this->apiKeys as \$name => \$key) \$headers[] = \$name . ': ' . \$key;
";
        $mcpConnectCode .= "        curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
";
        $mcpConnectCode .= "        // Not fully implemented event loop for SSE due to blocking PHP nature, just dummy for now
";
        $mcpConnectCode .= "        return new class(\$url) {
";
        $mcpConnectCode .= "            private \$url;
";
        $mcpConnectCode .= "            public function __construct(\$url) { \$this->url = \$url; }
";
        $mcpConnectCode .= "            public function get_tools() { return []; }
";
        $mcpConnectCode .= "        };
";
        $mcpConnectCode .= "    }
";

        $out = \Cdd\Paths\append_method_to_class($out, $mcpConnectCode);
    }

    return $out;
}
