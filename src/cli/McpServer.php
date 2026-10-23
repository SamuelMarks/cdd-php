<?php

/**
 * Model Context Protocol (MCP) server and LLM sampling handler for `cdd-php`.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Handles MCP JSON-RPC stdio server protocol and LLM sampling requests.
 */
class McpServer
{
    /**
     * Initiates an LLM sampling request over MCP stdio.
     *
     * @param array<int, array<string, mixed>> $messages Conversation messages.
     * @param int $maxTokens Maximum tokens to sample.
     * @param string $systemPrompt Optional system prompt.
     * @param resource|null $inStream Optional input stream override.
     * @param resource|null $outStream Optional output stream override.
     * @return mixed Sampling result or null on EOF.
     * @throws \Cdd\Exceptions\CddException If the MCP client returns an error response.
     */
    public static function sampleLlm(
        array $messages,
        int $maxTokens = 100,
        string $systemPrompt = '',
        mixed $inStream = null,
        mixed $outStream = null
    ): mixed {
        $id = uniqid();
        $req = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'sampling/createMessage',
            'params' => ['messages' => $messages, 'maxTokens' => $maxTokens],
        ];
        if ($systemPrompt !== '') {
            $req['params']['systemPrompt'] = $systemPrompt;
        }
        $out = $outStream ?: CddCli::$testOutStream ?: (defined('CDD_TEST_STDOUT') ? CDD_TEST_STDOUT : STDOUT);
        $in = $inStream ?: CddCli::$testInStream ?: (defined('CDD_TEST_STDIN') ? CDD_TEST_STDIN : STDIN);
        fwrite($out, json_encode($req) . "
");
        fflush($out);
        while (($line = fgets($in)) !== false) {
            $res = json_decode($line, true);
            if ($res && isset($res['id']) && $res['id'] === $id) {
                if (isset($res['error'])) {
                    throw \Cdd\Exceptions\CddException::mcpError($res['error']['message'] ?? 'Sampling failed');
                }
                return $res['result'] ?? null;
            }
        }
        return null;
    }

    /**
     * Invokes an MCP tool over an in-memory or stream transport with optional human-in-the-loop approval.
     *
     * @param string $name Tool name to invoke.
     * @param array<string, mixed> $arguments Tool arguments map.
     * @param callable|null $approvalCallback Optional callback `fn(string $name, array $args): bool` for tool execution approval.
     * @return array<string, mixed> MCP `CallToolResult` structure.
     */
    public static function callTool(
        string $name,
        array $arguments = [],
        ?callable $approvalCallback = null
    ): array {
        if ($approvalCallback !== null && !$approvalCallback($name, $arguments)) {
            return [
                'isError' => true,
                'content' => [['type' => 'text', 'text' => 'User denied execution of tool: ' . $name]],
            ];
        }
        $in = fopen('php://memory', 'r+');
        $req = [
            'jsonrpc' => '2.0',
            'id' => 'call_' . uniqid(),
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => $arguments,
            ],
        ];
        fwrite($in, json_encode($req) . "\n");
        rewind($in);
        ob_start();
        self::run($in);
        $rawOut = (string) ob_get_clean();
        fclose($in);
        $decoded = json_decode(trim($rawOut), true);
        return (array) ($decoded['result'] ?? ['isError' => true, 'content' => []]);
    }

    /**
     * Reads an MCP resource URI (`cdd://ast`, `cdd://schema`, or `file://`) with root boundary enforcement.
     *
     * @param string $uri Resource URI to read.
     * @return array<string, mixed> MCP `ReadResourceResult` or error structure.
     */
    public static function readResource(string $uri): array
    {
        $in = fopen('php://memory', 'r+');
        $req = [
            'jsonrpc' => '2.0',
            'id' => 'read_' . uniqid(),
            'method' => 'resources/read',
            'params' => ['uri' => $uri],
        ];
        fwrite($in, json_encode($req) . "\n");
        rewind($in);
        ob_start();
        self::run($in);
        $rawOut = (string) ob_get_clean();
        fclose($in);
        $decoded = json_decode(trim($rawOut), true);
        if (isset($decoded['error'])) {
            return ['error' => $decoded['error']];
        }
        return (array) ($decoded['result'] ?? ['contents' => []]);
    }

    /**
     * Builds a JSON AST summary of PHP classes and functions in the given directory.
     *
     * @param string $dir Directory to inspect.
     * @return string JSON-encoded AST summary.
     */
    public static function inspectAstSummary(string $dir): string
    {
        $summary = ['classes' => [], 'functions' => []];
        $phpFiles = array_merge(
            glob($dir . '/*.php') ?: [],
            glob($dir . '/src/*.php') ?: [],
            glob($dir . '/src/*/*.php') ?: []
        );
        sort($phpFiles);
        foreach (array_unique($phpFiles) as $file) {
            $code = (string) file_get_contents($file);
            foreach (\Cdd\Classes\parse($code) as $cls) {
                if ($cls['name'] !== '') {
                    $summary['classes'][] = [
                        'name' => $cls['name'],
                        'componentType' => $cls['componentType'],
                        'file' => basename($file),
                    ];
                }
            }
            foreach (\Cdd\Functions\parse($code) as $fn) {
                $summary['functions'][] = [
                    'name' => $fn['name'],
                    'file' => basename($file),
                ];
            }
        }
        return (string) json_encode($summary, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Runs the Model Context Protocol (MCP) JSON-RPC server over stdio.
     *
     * @param resource|null $inStream Optional input stream override.
     * @return int Exit status code.
     */
    public static function run(mixed $inStream = null): int
    {
        $in = $inStream ?: CddCli::$testInStream ?: (defined('CDD_TEST_STDIN') ? CDD_TEST_STDIN : STDIN);
        while (($line = fgets($in)) !== false) {
            $req = json_decode($line, true);
            if (!$req) {
                continue;
            }
            $isNotification = !array_key_exists('id', $req);
            $res = ['jsonrpc' => '2.0', 'id' => $req['id'] ?? null];
            if (isset($req['method'])) {
                if ($req['method'] === 'initialize') {
                    $res['result'] = [
                        'protocolVersion' => '2024-11-05',
                        'capabilities' => [
                            'tools' => ['listChanged' => true],
                            'resources' => ['listChanged' => true, 'subscribe' => true],
                            'prompts' => ['listChanged' => true],
                            'logging' => (object) [],
                        ],
                        'serverInfo' => ['name' => 'cdd-php-mcp', 'version' => '0.0.4'],
                    ];
                } elseif ($req['method'] === 'initialized') {
                    continue;
                } elseif ($req['method'] === 'ping') {
                    $res['result'] = [];
                } elseif ($req['method'] === 'prompts/list') {
                    $res['result'] = ['prompts' => []];
                    if (isset($req['params']['cursor'])) {
                        $res['result']['nextCursor'] = null;
                    }
                } elseif ($req['method'] === 'prompts/get') {
                    $res['error'] = ['code' => -32602, 'message' => 'Prompt not found'];
                } elseif ($req['method'] === 'logging/setLevel') {
                    $res['result'] = [];
                } elseif ($req['method'] === 'resources/templates/list') {
                    $res['result'] = ['resourceTemplates' => []];
                    if (isset($req['params']['cursor'])) {
                        $res['result']['nextCursor'] = null;
                    }
                } elseif ($req['method'] === 'completion/complete') {
                    $res['result'] = ['completion' => ['values' => [], 'hasMore' => false]];
                } elseif ($req['method'] === 'notifications/cancelled' || $req['method'] === 'notifications/progress') {
                    continue;
                } elseif ($req['method'] === 'resources/list') {
                    $resources = [
                        ['uri' => 'cdd://ast', 'name' => 'Internal AST Query Resource', 'mimeType' => 'application/json'],
                        ['uri' => 'cdd://schema', 'name' => 'Schema Inspection Resource', 'mimeType' => 'application/json'],
                    ];
                    $cursor = $req['params']['cursor'] ?? null;
                    $limit = (int) ($req['params']['limit'] ?? 50);
                    $offset = $cursor ? (int) $cursor : 0;
                    $sliced = array_slice($resources, $offset, $limit);
                    $nextOffset = $offset + $limit;
                    $nextCursor = $nextOffset < count($resources) ? (string) $nextOffset : null;

                    $res['result'] = ['resources' => $sliced];
                    if ($nextCursor) {
                        $res['result']['nextCursor'] = $nextCursor;
                    }
                } elseif ($req['method'] === 'resources/read') {
                    $uri = (string) ($req['params']['uri'] ?? '');
                    if ($uri === 'cdd://ast' || $uri === 'cdd://schema') {
                        $cwd = (string) getcwd();
                        if ($uri === 'cdd://schema') {
                            $tmpFile = (string) tempnam(sys_get_temp_dir(), 'cdd_');
                            ob_start();
                            CddCli::run(['cdd-php', 'to_openapi', '-i', $cwd, '-o', $tmpFile]);
                            ob_end_clean();
                            $text = file_exists($tmpFile) ? (string) file_get_contents($tmpFile) : '{}';
                            if (file_exists($tmpFile)) {
                                unlink($tmpFile);
                            }
                        } else {
                            $text = self::inspectAstSummary($cwd);
                        }
                        $res['result'] = ['contents' => [['uri' => $uri, 'mimeType' => 'application/json', 'text' => $text]]];
                    } elseif (str_starts_with($uri, 'file://')) {
                        $path = substr($uri, 7);
                        $realPath = realpath($path);
                        $realCwd = realpath((string) getcwd());
                        $cwdBoundary = $realCwd !== false ? rtrim($realCwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
                        if (
                            $realPath !== false
                            && $realCwd !== false
                            && (str_starts_with($realPath, $cwdBoundary) || $realPath === $realCwd)
                            && file_exists($realPath)
                            && is_file($realPath)
                        ) {
                            $res['result'] = ['contents' => [['uri' => $uri, 'mimeType' => 'text/plain', 'text' => (string) file_get_contents($realPath)]]];
                        } else {
                            $res['error'] = ['code' => -32602, 'message' => 'Access denied: Path is outside root boundary or invalid'];
                        }
                    } else {
                        $res['error'] = ['code' => -32602, 'message' => 'Invalid URI'];
                    }
                } elseif ($req['method'] === 'tools/list') {
                    $tools = [
                        [
                            'name' => 'from_openapi',
                            'description' => 'Generate code from OpenAPI spec',
                            'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'output' => ['type' => 'string']], 'required' => ['input', 'output']],
                        ],
                        [
                            'name' => 'to_openapi',
                            'description' => 'Generate OpenAPI spec from code',
                            'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'output' => ['type' => 'string']], 'required' => ['input', 'output']],
                        ],
                        [
                            'name' => 'sync',
                            'description' => 'Bidirectional sync code and schema',
                            'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'truth' => ['type' => 'string']], 'required' => ['input']],
                        ],
                        [
                            'name' => 'from_arazzo',
                            'description' => 'Generate PHP workflow SDK, CLI, or Server tests from Arazzo spec',
                            'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'output' => ['type' => 'string']], 'required' => ['input', 'output']],
                        ],
                        [
                            'name' => 'to_arazzo',
                            'description' => 'Generate Arazzo 1.1.0 spec from PHP workflow code',
                            'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'output' => ['type' => 'string']], 'required' => ['input', 'output']],
                        ],
                    ];

                    $cursor = $req['params']['cursor'] ?? null;
                    $limit = (int) ($req['params']['limit'] ?? 50);
                    $offset = $cursor ? (int) $cursor : 0;
                    $slicedTools = array_slice($tools, $offset, $limit);
                    $nextOffset = $offset + $limit;
                    $nextCursor = $nextOffset < count($tools) ? (string) $nextOffset : null;

                    $res['result'] = ['tools' => $slicedTools];
                    if ($nextCursor) {
                        $res['result']['nextCursor'] = $nextCursor;
                    }
                } elseif ($req['method'] === 'tools/call') {
                    $name = $req['params']['name'] ?? '';
                    $args = $req['params']['arguments'] ?? [];
                    try {
                        ob_start();
                        if ($name === 'from_openapi') {
                            CddCli::run(['cdd-php', 'from_openapi', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'to_openapi') {
                            CddCli::run(['cdd-php', 'to_openapi', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'from_arazzo') {
                            CddCli::run(['cdd-php', 'from_arazzo', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'to_arazzo') {
                            CddCli::run(['cdd-php', 'to_arazzo', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'sync') {
                            $syncArgs = ['cdd-php', 'sync', '-i', $args['input']];
                            if (isset($args['truth'])) {
                                $syncArgs[] = '-t';
                                $syncArgs[] = $args['truth'];
                            }
                            CddCli::run($syncArgs);
                        } else {
                            throw \Cdd\Exceptions\CddException::mcpError('Unknown tool');
                        }
                        $output = ob_get_clean();
                        $res['result'] = ['content' => [['type' => 'text', 'text' => $output]]];
                    } catch (\Throwable $e) {
                        ob_end_clean();
                        $res['result'] = ['content' => [['type' => 'text', 'text' => 'Error: ' . $e->getMessage()]], 'isError' => true];
                    }
                } else {
                    $res['error'] = ['code' => -32601, 'message' => 'Method not found'];
                }
            }
            if (!$isNotification) {
                echo json_encode($res) . "
";
            }
        }
        return 0;
    }
}
