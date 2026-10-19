<?php

/**
 * Command-line interface and programmatic SDK dispatcher for cdd-php.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * CddCli
 *
 * This class implements the command-line interface for the Cdd framework.
 * It provides tools to compile OpenAPI specifications to PHP code, extract
 * OpenAPI specs from PHP code, synchronize a directory bidirectionally,
 * and expose generator capabilities via the Model Context Protocol (MCP).
 */
class CddCli
{
    /**
     * @var resource|null Optional input stream override for testing MCP stdio.
     */
    public static mixed $testInStream = null;

    /**
     * @var resource|null Optional output stream override for testing MCP stdio.
     */
    public static mixed $testOutStream = null;

    /**
     * @var resource|null Optional pre-connected stream for testing serve_json_rpc accept loop.
     */
    public static mixed $testSocketConn = null;

    /**
     * Generate code from an OpenAPI specification.
     *
     * @param array<int, string>|CddConfig $args The command line arguments or config object
     * @return int The exit status code
     */
    public static function generate_from_openapi(array|CddConfig $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'from_openapi';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'from_openapi'], $args));
    }

    /**
     * Generate an OpenAPI specification from source code.
     *
     * @param array<int, string>|CddConfig $args The command line arguments or config object
     * @return int The exit status code
     */
    public static function generate_to_openapi(array|CddConfig $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'to_openapi';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'to_openapi'], $args));
    }

    /**
     * Generate code from an Arazzo specification.
     *
     * @param array<int, string>|CddConfig $args The command line arguments or config object
     * @return int The exit status code
     */
    public static function generate_from_arazzo(array|CddConfig $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'from_arazzo';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'from_arazzo'], $args));
    }

    /**
     * Generate an Arazzo specification from source code.
     *
     * @param array<int, string>|CddConfig $args The command line arguments or config object
     * @return int The exit status code
     */
    public static function generate_to_arazzo(array|CddConfig $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'to_arazzo';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'to_arazzo'], $args));
    }

    /**
     * Generate JSON documentation with code snippets for an OpenAPI specification.
     *
     * @param array<int, string>|CddConfig $args The command line arguments or config object
     * @return int The exit status code
     */
    public static function generate_docs_json(array|CddConfig $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'to_docs_json';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'to_docs_json'], $args));
    }

    /**
     * Expose CLI interface as a JSON-RPC server.
     *
     * @param array<int, string>|CddConfig $args The command line arguments or config object
     * @return int The exit status code
     */
    public static function serve_json_rpc(array|CddConfig $args): int
    {
        if ($args instanceof CddConfig) {
            $args->command = 'serve_json_rpc';
            return self::run($args->toArgs());
        }
        return self::run(array_merge(['cdd-php', 'serve_json_rpc'], $args));
    }

    /**
     * Executes an MCP sampling request back to the client over stdio.
     *
     * @param array<int, array<string, mixed>> $messages The chat messages for the LLM
     * @param int $maxTokens The maximum number of tokens to generate
     * @param string $systemPrompt The system prompt
     * @return array<string, mixed>|null The sampling result or null on failure
     * @throws \Exception If the client returns an MCP error response
     */
    public static function sample_llm(array $messages, int $maxTokens = 100, string $systemPrompt = ''): ?array
    {
        $id = uniqid();
        $req = ['jsonrpc' => '2.0', 'id' => $id, 'method' => 'sampling/createMessage', 'params' => ['messages' => $messages, 'maxTokens' => $maxTokens]];
        if ($systemPrompt !== '') {
            $req['params']['systemPrompt'] = $systemPrompt;
        }
        $outStream = self::$testOutStream ?: STDOUT;
        fwrite($outStream, json_encode($req) . "
");
        fflush($outStream);

        $inStream = self::$testInStream ?: STDIN;
        while (($line = fgets($inStream)) !== false) {
            $res = json_decode($line, true);
            if ($res && isset($res['id']) && $res['id'] === $id) {
                if (isset($res['error'])) {
                    throw new \Exception($res['error']['message'] ?? 'Sampling failed');
                }
                return $res['result'] ?? null;
            }
        }
        return null;
    }

    /**
     * Merges operation metadata map (`operationId => metadata`) into an OpenAPI Paths array.
     *
     * @param array<string, array<string, mixed>> $paths OpenAPI paths map.
     * @param array<string, array<string, mixed>> $opMap Map of operationId (or `/cli/<opId>`) to operation metadata.
     * @param string $keyPrefix Prefix to prepend to operationId when looking up in `$opMap`.
     * @return array<string, array<string, mixed>> Updated OpenAPI paths map.
     */
    public static function mergeOperationMetadata(array $paths, array $opMap, string $keyPrefix = ''): array
    {
        if (empty($opMap) || empty($paths)) {
            return $paths;
        }
        foreach ($paths as &$methods) {
            foreach ($methods as &$operation) {
                $opId = (string) ($operation['operationId'] ?? '');
                $lookupKey = $keyPrefix . $opId;
                if ($opId !== '' && isset($opMap[$lookupKey])) {
                    $operation = array_replace_recursive((array) $operation, $opMap[$lookupKey]);
                }
            }
        }
        return $paths;
    }

    /**
     * Parses `api_metadata.php` via AST without executing code and merges top-level fields into `$openapi`.
     *
     * @param array<string, mixed> $openapi Target OpenAPI specification array.
     * @param string $metadataPath Path to `api_metadata.php`.
     * @return array<string, mixed> Updated OpenAPI specification array.
     */
    public static function applyApiMetadata(array $openapi, string $metadataPath): array
    {
        if (!file_exists($metadataPath)) {
            return $openapi;
        }
        $parser = \Cdd\Classes\create_parser();
        $stmts = $parser->parse((string) file_get_contents($metadataPath)) ?? [];
        $nodeFinder = new \PhpParser\NodeFinder();
        /** @var \PhpParser\Node\Stmt\Return_|null $returnStmt */
        $returnStmt = $nodeFinder->findFirstInstanceOf($stmts, \PhpParser\Node\Stmt\Return_::class);
        $metadata = $returnStmt !== null ? \Cdd\Mocks\evaluateAstNode($returnStmt->expr) : null;
        if (is_array($metadata)) {
            foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
                if (isset($metadata[$key])) {
                    $openapi[$key] = $metadata[$key];
                }
            }
        }
        return $openapi;
    }

    /**
     * Processes a single HTTP JSON-RPC connection stream.
     *
     * @param resource $conn The accepted socket stream connection.
     * @param int $chunkSize Read buffer chunk size in bytes.
     * @return void
     */
    public static function handleJsonRpcConnection(mixed $conn, int $chunkSize = 8192): void
    {
        $crlf = chr(13) . chr(10);
        $sep = $crlf . $crlf;
        $request = '';
        while (($data = fread($conn, $chunkSize)) !== false && $data !== '') {
            $request .= $data;
            if (strpos($request, $sep) !== false) {
                break;
            }
        }
        $headersEnd = strpos($request, $sep);

        $body = $request;
        if ($headersEnd !== false) {
            $body = substr($request, $headersEnd + 4);
            if (preg_match('/Content-Length:\s*(\d+)/i', $request, $m)) {
                $len = (int) $m[1];
                while (strlen($body) < $len && ($chunk = fread($conn, $chunkSize)) !== false && $chunk !== '') {
                    $body .= $chunk;
                }
            }
        }

        $reqData = json_decode($body, true);
        $res = ['jsonrpc' => '2.0', 'id' => $reqData['id'] ?? null];

        if (!is_array($reqData) || !isset($reqData['method']) || !is_string($reqData['method'])) {
            $res['error'] = ['code' => -32600, 'message' => 'Invalid Request'];
        } else {
            $m = $reqData['method'];
            $p = $reqData['params'] ?? [];
            $cmdArgs = ['cdd-php', $m];
            if (is_array($p)) {
                foreach ($p as $k => $v) {
                    if (is_int($k)) {
                        $cmdArgs[] = (string) $v;
                    } else {
                        $cmdArgs[] = '--' . $k;
                        $cmdArgs[] = (string) $v;
                    }
                }
            }
            ob_start();
            self::run($cmdArgs);
            $res['result'] = (string) ob_get_clean();
        }
        $resBody = (string) json_encode($res);
        $response = "HTTP/1.1 200 OK{$crlf}Content-Type: application/json{$crlf}Content-Length: " . strlen($resBody) . $sep . $resBody;
        fwrite($conn, $response);
        fclose($conn);
    }

    /**
     * Prints top-level or subcommand-specific CLI help text.
     *
     * @param string $command The subcommand name or empty string for top-level help.
     * @param string $subcommand Optional target subcommand (e.g., `to_sdk`, `to_sdk_cli`, `to_server`).
     * @return int Exit status code (0 if help was printed, -1 if command is not a known help target).
     */
    public static function printHelp(string $command = '', string $subcommand = ''): int
    {
        if ($command === '' || $command === '--help' || $command === '-h') {
            echo "cdd-php CLI\n";
            echo "Usage:\n";
            echo "  cdd-php [subcommand] [options]\n";
            echo "\nSubcommands:\n";
            echo "  from_openapi    Generate code from an OpenAPI specification.\n";
            echo "  to_openapi      Generate an OpenAPI specification from source code.\n";
            echo "  from_arazzo     Generate code from an Arazzo workflow specification.\n";
            echo "  to_arazzo       Generate an Arazzo workflow specification from source code.\n";
            echo "  to_docs_json    Generate JSON documentation with code snippets for an OpenAPI specification.\n";
            echo "  serve_json_rpc  Expose CLI interface as a JSON-RPC server.\n";
            echo "  sync            Synchronize an OpenAPI specification with source code.\n";
            echo "  mcp             Run the Model Context Protocol (MCP) server over stdio.\n";
            echo "\nOptions:\n";
            echo "  --help, -h      Show this help message\n";
            echo "  --version, -v   Show version information\n";
            echo "\nExamples:\n";
            echo "  cdd-php serve_json_rpc [--port|-p 8080] [--listen|-l 127.0.0.1]\n";
            echo "  cdd-php to_docs_json --no-imports --no-wrapping -i spec.json -o docs.json\n";
            echo "  cdd-php from_openapi to_sdk_cli -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests] [--mcp]\n";
            echo "  cdd-php from_openapi to_sdk -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests] [--mcp]\n";
            echo "  cdd-php from_openapi to_server -i spec.json -o target_directory\n";
            echo "  cdd-php sync -i directory -t class\n";
            return 0;
        }
        if ($command === 'from_openapi') {
            $targetDescriptions = [
                'to_sdk' => 'Generate a client SDK from an OpenAPI specification.',
                'to_sdk_cli' => 'Generate a client SDK and a corresponding CLI from an OpenAPI specification.',
                'to_server' => 'Generate server boilerplate, models, and routing logic from an OpenAPI specification.',
            ];
            if (isset($targetDescriptions[$subcommand])) {
                echo "Usage: cdd-php from_openapi {$subcommand} [options]\n\n";
                echo $targetDescriptions[$subcommand] . "\n\n";
            } else {
                echo "Usage: cdd-php from_openapi <target> [options]\n\n";
                echo "Targets:\n";
                echo "  to_sdk_cli      Generate a client SDK and a corresponding CLI\n";
                echo "  to_sdk          Generate a client SDK\n";
                echo "  to_server       Generate server boilerplate, models, and routing logic\n\n";
            }
            echo "Options:\n";
            echo "  -i, --input <spec>    Path to the OpenAPI specification file\n";
            echo "  -d, --input-dir <dir> Path to a directory containing OpenAPI specifications\n";
            echo "  -o, --output <dir>    Destination path for generation\n";
            echo "  --no-github-actions   Disable GitHub Actions generation\n";
            echo "  --no-installable-package Disable Composer package generation\n";
            echo "  --tests               Generate PHPUnit tests\n";
            echo "  --mcp                 Generate MCP server\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        if ($command === 'to_openapi') {
            echo "Usage: cdd-php to_openapi [options]\n\n";
            echo "Options:\n";
            echo "  -i, --input <path>    Path to the source code directory or file to parse\n";
            echo "  -o, --output <path>   Destination file for the OpenAPI specification\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        if ($command === 'to_docs_json') {
            echo "Usage: cdd-php to_docs_json [options]\n\n";
            echo "Options:\n";
            echo "  -i, --input <spec>    Path to the OpenAPI specification file\n";
            echo "  -o, --output <path>   Destination file for the JSON documentation\n";
            echo "  --no-imports          Disable import statements in the generated documentation\n";
            echo "  --no-wrapping         Disable line wrapping in the generated documentation\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        if ($command === 'serve_json_rpc') {
            echo "Usage: cdd-php serve_json_rpc [options]\n\n";
            echo "Options:\n";
            echo "  -p, --port <port>     Port to listen on (e.g., 8080)\n";
            echo "  -l, --listen <addr>   Address to bind to (e.g., 127.0.0.1)\n";
            echo "  --timeout <seconds>   Socket accept timeout in seconds\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        if ($command === 'sync') {
            echo "Usage: cdd-php sync [options]\n\n";
            echo "Options:\n";
            echo "  -i, --input <directory> Target directory to synchronize\n";
            echo "  -t, --truth <source>  Source of truth for synchronization (e.g., 'class', 'spec')\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        if ($command === 'mcp') {
            echo "Usage: cdd-php mcp\n\n";
            echo "Run the generator as an MCP server over stdio.\n";
            return 0;
        }
        if ($command === 'from_arazzo') {
            echo "Usage: cdd-php from_arazzo <target> [options]\n\n";
            echo "Targets:\n";
            echo "  to_sdk_cli      Generate a workflow client SDK and CLI runner\n";
            echo "  to_sdk          Generate a workflow client SDK\n";
            echo "  to_server       Generate a server workflow engine and E2E workflow tests\n\n";
            echo "Options:\n";
            echo "  -i, --input <spec>    Path to the Arazzo specification file\n";
            echo "  -d, --input-dir <dir> Path to a directory containing Arazzo specifications\n";
            echo "  -o, --output <dir>    Destination path for generation\n";
            echo "  --no-github-actions   Disable GitHub Actions generation\n";
            echo "  --no-installable-package Disable Composer package generation\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        if ($command === 'to_arazzo') {
            echo "Usage: cdd-php to_arazzo [options]\n\n";
            echo "Options:\n";
            echo "  -i, --input <path>    Path to the workflow source file or directory to parse\n";
            echo "  -o, --output <path>   Destination file for the Arazzo specification\n";
            echo "  --help, -h            Show this help message\n";
            return 0;
        }
        return -1;
    }

    /**
     * Runs the Model Context Protocol (MCP) JSON-RPC server over stdio.
     *
     * @return int Exit status code.
     */
    public static function runMcpServer(): int
    {
        $capabilities = ['tools' => ['listChanged' => true], 'resources' => ['listChanged' => true, 'subscribe' => true], 'prompts' => ['listChanged' => true], 'logging' => (object) []];
        $inStream = self::$testInStream ?: (defined('CDD_TEST_STDIN') ? CDD_TEST_STDIN : STDIN);
        while (($line = fgets($inStream)) !== false) {
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
                        'capabilities' => $capabilities,
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
                } elseif ($req['method'] === 'notifications/cancelled') {
                    continue;
                } elseif ($req['method'] === 'notifications/progress') {
                    continue;
                } elseif ($req['method'] === 'resources/list') {
                    $resources = [
                        ['uri' => 'cdd://ast', 'name' => 'Internal AST Query Resource', 'mimeType' => 'text/plain'],
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
                    $uri = $req['params']['uri'] ?? '';
                    if ($uri === 'cdd://ast' || $uri === 'cdd://schema') {
                        $tmpFile = (string) tempnam(sys_get_temp_dir(), 'cdd_');
                        if ($uri === 'cdd://schema') {
                            ob_start();
                            self::run(['cdd-php', 'to_openapi', '-i', (string) getcwd(), '-o', $tmpFile]);
                            ob_end_clean();
                            $text = file_exists($tmpFile) ? (string) file_get_contents($tmpFile) : '{}';
                        } else {
                            $text = '{}';
                        }
                        @unlink($tmpFile);
                        $res['result'] = ['contents' => [['uri' => $uri, 'mimeType' => 'text/plain', 'text' => $text]]];
                    } elseif (strpos($uri, 'file://') === 0) {
                        $path = substr($uri, 7);
                        $realPath = realpath($path);
                        $realCwd = (string) realpath((string) getcwd());
                        if ($realPath !== false && strpos($realPath, $realCwd) === 0 && file_exists($realPath) && is_file($realPath)) {
                            $res['result'] = ['contents' => [['uri' => $uri, 'mimeType' => 'text/plain', 'text' => file_get_contents($realPath)]]];
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
                            self::run(['cdd-php', 'from_openapi', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'to_openapi') {
                            self::run(['cdd-php', 'to_openapi', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'from_arazzo') {
                            self::run(['cdd-php', 'from_arazzo', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'to_arazzo') {
                            self::run(['cdd-php', 'to_arazzo', '-i', $args['input'], '-o', $args['output']]);
                        } elseif ($name === 'sync') {
                            $syncArgs = ['cdd-php', 'sync', '-i', $args['input']];
                            if (isset($args['truth'])) {
                                $syncArgs[] = '-t';
                                $syncArgs[] = $args['truth'];
                            }
                            self::run($syncArgs);
                        } else {
                            throw new \Exception('Unknown tool');
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

    /**
     * Runs the TCP HTTP JSON-RPC server (`serve_json_rpc`).
     *
     * @param array<int, string> $argv CLI arguments.
     * @return int Exit status code.
     */
    public static function runServeJsonRpc(array $argv): int
    {
        $argc = count($argv);
        $port = (int) ($_ENV['CDD_PORT'] ?? 8080);
        $listen = $_ENV['CDD_LISTEN'] ?? '127.0.0.1';
        $timeout = (float) ($_ENV['CDD_TIMEOUT'] ?? ini_get('default_socket_timeout'));
        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '--port' || $argv[$i] === '-p') && isset($argv[$i + 1])) {
                $port = (int) $argv[++$i];
            } elseif (($argv[$i] === '--listen' || $argv[$i] === '-l') && isset($argv[$i + 1])) {
                $listen = $argv[++$i];
            } elseif ($argv[$i] === '--timeout' && isset($argv[$i + 1])) {
                $timeout = (float) $argv[++$i];
            }
        }
        $serverUrl = "tcp://$listen:$port";
        $socket = @stream_socket_server($serverUrl, $errno, $errstr);
        if (!$socket) {
            fwrite(STDERR, "Error starting server: $errstr ($errno)
");
            return 1;
        }
        echo "JSON-RPC server listening on $serverUrl
";
        while (true) {
            $conn = self::$testSocketConn;
            self::$testSocketConn = null;
            $conn = $conn ?: @stream_socket_accept($socket, $timeout);
            if (!$conn) {
                break;
            }
            self::handleJsonRpcConnection($conn);
        }
        fclose($socket);
        return 0;
    }

    /**
     * Runs the bidirectional `sync` command.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runSync(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $dir = '';
        $truth = '';
        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $dir = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-t' || $argv[$i] === '--truth') && isset($argv[$i + 1])) {
                $truth = $argv[$i + 1];
                $i++;
            } elseif ($dir === '') {
                $dir = $resolvePath($argv[$i]);
            }
        }

        if (!$dir || !is_dir($dir)) {
            echo "Error: Directory not found.
";
            return 1;
        }

        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Synced API', 'version' => '0.0.4'],
            'paths' => [],
            'components' => ['schemas' => []],
        ];

        if ($truth === 'spec' && file_exists("$dir/openapi.json")) {
            $openapi = \Cdd\Openapi\parse((string) file_get_contents("$dir/openapi.json"));
        }

        $openapi = self::applyApiMetadata($openapi, "$dir/src/api_metadata.php");

        if (file_exists("$dir/src/routes.php")) {
            $routes = \Cdd\Routes\parse((string) file_get_contents("$dir/src/routes.php"));
            if (!empty($routes)) {
                $openapi['paths'] = array_replace_recursive((array) $openapi['paths'], $routes);
            }
        }

        if (file_exists("$dir/src/ApiClient.php")) {
            $clientPaths = \Cdd\Client\parse((string) file_get_contents("$dir/src/ApiClient.php"));
            if (!empty($clientPaths)) {
                $openapi['paths'] = array_replace_recursive((array) $openapi['paths'], $clientPaths);
            }
        }

        if (file_exists("$dir/src/ApiController.php")) {
            $controllerOps = \Cdd\Controllers\parse((string) file_get_contents("$dir/src/ApiController.php"));
            $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $controllerOps);
        }

        if (file_exists("$dir/src/api_cli.php")) {
            $cliOps = \Cdd\Cli\parse((string) file_get_contents("$dir/src/api_cli.php"));
            $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $cliOps, '/cli/');
        }

        if (file_exists("$dir/src/Models.php")) {
            $classes = \Cdd\Classes\parse((string) file_get_contents("$dir/src/Models.php"));
            if (!empty($classes)) {
                $openapi['components'] = \Cdd\Components\parse_classes($classes, (array) $openapi['components']);
            }
        }

        if (file_exists("$dir/src/mocks.php")) {
            $mocks = \Cdd\Mocks\parse((string) file_get_contents("$dir/src/mocks.php"));
            if (!empty($mocks)) {
                $openapi['components']['examples'] = $mocks;
                $openapi['components']['schemas'] = \Cdd\Components\infer_schemas_from_mocks(
                    $mocks,
                    (array) ($openapi['components']['schemas'] ?? [])
                );
            }
        }

        if (file_exists("$dir/src/ApiServers.php")) {
            $parsedServers = \Cdd\Servers\parse((string) file_get_contents("$dir/src/ApiServers.php"));
            if (!empty($parsedServers)) {
                $openapi['servers'] = $parsedServers;
            }
        }

        if (file_exists("$dir/Webhooks.php")) {
            $webhooks = \Cdd\Webhooks\parse((string) file_get_contents("$dir/Webhooks.php"));
            if (!empty($webhooks)) {
                $openapi['webhooks'] = $webhooks;
            }
        }

        $testedPaths = [];
        if (file_exists("$dir/src/ComposableTests.php")) {
            $testedPaths = \Cdd\Tests\parse((string) file_get_contents("$dir/src/ComposableTests.php"));
        } elseif (file_exists("$dir/src/ApiTests.php")) {
            $testedPaths = \Cdd\Tests\parse((string) file_get_contents("$dir/src/ApiTests.php"));
        }
        foreach ($testedPaths as $testedMethod => $pathMap) {
            if (is_array($pathMap)) {
                foreach (array_keys($pathMap) as $testedPath) {
                    if (!isset($openapi['paths'][$testedPath][$testedMethod])) {
                        $openapi['paths'][$testedPath][$testedMethod] = [
                            'operationId' => $testedMethod . preg_replace('/[^a-zA-Z0-9]/', '', (string) $testedPath),
                            'responses' => ['200' => ['description' => 'Successful operation']],
                        ];
                    }
                }
            }
        }

        $options = [];
        if (file_exists("$dir/src/ComposableTests.php") || file_exists("$dir/src/ApiTests.php") || file_exists("$dir/src/mocks.php")) {
            $options['tests'] = true;
        }
        $json = \Cdd\Openapi\emit($openapi, $dir, $options);
        file_put_contents("$dir/openapi.json", $json);

        if (file_exists("$dir/arazzo.json") || file_exists("$dir/src/ApiWorkflows.php")) {
            $arazzo = [];
            if ($truth === 'spec' && file_exists("$dir/arazzo.json")) {
                $arazzo = \Cdd\Arazzo\parse((string) file_get_contents("$dir/arazzo.json"));
            } elseif (file_exists("$dir/src/ApiWorkflows.php")) {
                $arazzo = \Cdd\ArazzoClient\parse((string) file_get_contents("$dir/src/ApiWorkflows.php"));
            } elseif (file_exists("$dir/arazzo.json")) {
                $arazzo = \Cdd\Arazzo\parse((string) file_get_contents("$dir/arazzo.json"));
            }
            if (!empty($arazzo)) {
                $arazzoJson = \Cdd\Arazzo\emit($arazzo, $dir, ['subcommand' => 'all']);
                file_put_contents("$dir/arazzo.json", $arazzoJson);
            }
        }

        echo "Synchronized codebase in $dir successfully.
";
        return 0;
    }

    /**
     * Runs the `to_openapi` subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToOpenApi(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $file = '';
        $outFile = '';
        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $outFile = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif ($file === '') {
                $file = $resolvePath($argv[$i]);
            }
        }

        if (!$file || !file_exists($file)) {
            echo "Error: File not found.
";
            return 1;
        }

        $code = is_dir($file) ? '' : (string) file_get_contents($file);

        $openapi = [
            'openapi' => '3.2.0',
            'info' => [
                'title' => 'Parsed API',
                'version' => '0.0.4',
            ],
            'paths' => [],
            'components' => ['schemas' => []],
        ];

        $dir = is_dir($file) ? $file : dirname($file);
        $openapi = self::applyApiMetadata($openapi, "$dir/src/api_metadata.php");

        if (strpos($code, 'curl_exec') !== false) {
            $openapi['paths'] = \Cdd\Client\parse($code);
        } else {
            $routes = \Cdd\Routes\parse($code);
            if (!empty($routes)) {
                $openapi['paths'] = $routes;
            }

            if (!empty($openapi['paths'])) {
                $controllerOps = \Cdd\Controllers\parse($code);
                $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $controllerOps);

                $cliOps = \Cdd\Cli\parse($code);
                $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $cliOps, '/cli/');
            }

            $servers = \Cdd\Servers\parse($code);
            if (!empty($servers)) {
                $openapi['servers'] = $servers;
            }

            $classes = \Cdd\Classes\parse($code);
            if (!empty($classes)) {
                $openapi['components'] = \Cdd\Components\parse_classes($classes, (array) $openapi['components']);
            }
        }

        if (empty((array) $openapi['paths'])) {
            unset($openapi['paths']);
        }
        if (empty((array) $openapi['components']['schemas'])) {
            unset($openapi['components']['schemas']);
        }
        if (empty((array) ($openapi['components'] ?? []))) {
            unset($openapi['components']);
        }

        $outStr = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "
";
        if ($outFile !== '') {
            file_put_contents($outFile, $outStr);
            echo "Emitted OpenAPI to $outFile
";
        } else {
            echo $outStr;
        }
        return 0;
    }

    /**
     * Runs the `from_openapi` (or `emit`) subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runFromOpenApi(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $subcommand = 'to_sdk';
        $file = '';
        $inputDir = '';
        $dir = (string) getcwd();

        $noGithubActions = false;
        $noInstallablePackage = false;
        $tests = false;
        $mcp = false;

        $newArgv = [];
        for ($k = 0; $k < $argc; $k++) {
            if ($argv[$k] === '--no-github-actions') {
                $noGithubActions = true;
            } elseif ($argv[$k] === '--no-installable-package') {
                $noInstallablePackage = true;
            } elseif ($argv[$k] === '--tests') {
                $tests = true;
            } elseif ($argv[$k] === '--mcp') {
                $mcp = true;
            } else {
                $newArgv[] = $argv[$k];
            }
        }
        $argv = $newArgv;
        $argc = count($argv);

        $i = 2;
        if (isset($argv[$i]) && in_array($argv[$i], ['to_sdk_cli', 'to_sdk', 'to_server'], true)) {
            $subcommand = $argv[$i];
            $i++;
        }

        for (; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-d' || $argv[$i] === '--input-dir') && isset($argv[$i + 1])) {
                $inputDir = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $dir = $resolvePath($argv[$i + 1]);
                $i++;
            } else {
                $dir = $resolvePath($argv[$i]);
            }
        }

        if ($file === '' && $inputDir === '') {
            echo "Error: Spec file or input dir not provided.
";
            return 1;
        }

        $specFile = $file;
        if ($specFile !== '') {
            if (!file_exists($specFile)) {
                echo "Error: Spec file not found ($specFile).
";
                return 1;
            }
        } else {
            if (!is_dir($inputDir)) {
                echo "Error: Input directory not found.
";
                return 1;
            }
            $files = glob("$inputDir/*.json");
            if (empty($files)) {
                echo "Error: No .json files found in input dir.
";
                return 1;
            }
            $specFile = $files[0];
        }

        $spec = \Cdd\Openapi\parse((string) file_get_contents($specFile));
        \Cdd\Openapi\emit($spec, $dir, [
            'no_github_actions' => $noGithubActions,
            'no_installable_package' => $noInstallablePackage,
            'tests' => $tests,
            'mcp' => $mcp,
            'subcommand' => $subcommand,
        ]);
        if ($subcommand === 'to_sdk_cli') {
            $cliCode = \Cdd\Cli\emit($spec['paths'] ?? []);
            file_put_contents("$dir/src/api_cli.php", $cliCode);
        }

        echo "Emitted code to $dir successfully.
";
        return 0;
    }

    /**
     * Runs the `to_docs_json` subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToDocsJson(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $file = '';
        $outFileDocs = '';
        $noImports = false;
        $noWrapping = false;

        for ($i = 2; $i < $argc; $i++) {
            if ($argv[$i] === '--no-imports') {
                $noImports = true;
            } elseif ($argv[$i] === '--no-wrapping') {
                $noWrapping = true;
            } elseif (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $outFileDocs = $resolvePath($argv[$i + 1]);
                $i++;
            }
        }

        if (!$file || !file_exists($file)) {
            fwrite(STDERR, "Error: Spec file not found.
");
            return 1;
        }

        $spec = json_decode((string) file_get_contents($file), true);
        if (!$spec) {
            fwrite(STDERR, "Error parsing JSON spec.
");
            return 1;
        }

        $operations = [];
        if (isset($spec['paths'])) {
            foreach ($spec['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    if (in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers'], true)) {
                        continue;
                    }

                    $opId = $operation['operationId'] ?? strtolower((string) $method) . preg_replace('/[^a-zA-Z0-9]/', '', (string) $path);

                    $camelOpId = (string) preg_replace_callback('/[-_](.)/', static function (array $m): string {
                        return strtoupper($m[1]);
                    }, (string) $opId);

                    $params = [];
                    if (isset($operation['parameters'])) {
                        foreach ($operation['parameters'] as $p) {
                            $name = $p['name'] ?? 'param';
                            $params[] = "'$name' => 'value'";
                        }
                    }
                    if (isset($operation['requestBody'])) {
                        $params[] = "'body' => [...]";
                    }
                    $paramStr = empty($params) ? '' : '[' . implode(', ', $params) . ']';

                    $snippet = "\$response = \$client->{$camelOpId}($paramStr);
print_r(\$response);";

                    $codeBlock = [
                        'snippet' => $snippet,
                    ];

                    if (!$noImports) {
                        $codeBlock['imports'] = "require_once 'vendor/autoload.php';
use ApiClient;";
                    }

                    if (!$noWrapping) {
                        $codeBlock['wrapper_start'] = "\$client = new ApiClient('https://api.example.com');";
                        $codeBlock['wrapper_end'] = '';
                    }

                    $operations[] = [
                        'method' => strtoupper((string) $method),
                        'path' => $path,
                        'operationId' => $opId,
                        'code' => $codeBlock,
                    ];
                }
            }
        }

        $result = [
            [
                'language' => 'php',
                'operations' => $operations,
            ],
        ];

        $outStr = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "
";
        if ($outFileDocs !== '') {
            if (is_dir($outFileDocs)) {
                $outFileDocs = rtrim($outFileDocs, '/') . '/docs.json';
            }
            file_put_contents($outFileDocs, $outStr);
        } else {
            echo $outStr;
        }
        return 0;
    }

    /**
     * Runs the `from_arazzo` subcommand to compile an Arazzo specification into PHP SDK, CLI, or Server artifacts.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runFromArazzo(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $subcommand = 'to_sdk';
        $file = '';
        $inputDir = '';
        $dir = (string) getcwd();
        $noGithubActions = false;
        $noInstallablePackage = false;

        $i = 2;
        if (isset($argv[$i]) && in_array($argv[$i], ['to_sdk_cli', 'to_sdk', 'to_server', 'all'], true)) {
            $subcommand = $argv[$i];
            $i++;
        }

        for (; $i < $argc; $i++) {
            if ($argv[$i] === '--no-github-actions') {
                $noGithubActions = true;
            } elseif ($argv[$i] === '--no-installable-package') {
                $noInstallablePackage = true;
            } elseif (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[++$i]);
            } elseif (($argv[$i] === '-d' || $argv[$i] === '--input-dir') && isset($argv[$i + 1])) {
                $inputDir = $resolvePath($argv[++$i]);
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $dir = $resolvePath($argv[++$i]);
            }
        }

        if ($file === '' && $inputDir !== '' && is_dir($inputDir)) {
            $candidates = glob("$inputDir/*.json");
            if (!empty($candidates)) {
                $file = $candidates[0];
            }
        }

        if ($file === '' || !file_exists($file)) {
            echo "Error: Arazzo spec file not found.\n";
            return 1;
        }

        $spec = \Cdd\Arazzo\parse((string) file_get_contents($file));
        \Cdd\Arazzo\emit($spec, $dir, [
            'subcommand' => $subcommand,
            'no_github_actions' => $noGithubActions,
            'no_installable_package' => $noInstallablePackage,
        ]);

        echo "Emitted Arazzo code to $dir successfully.\n";
        return 0;
    }

    /**
     * Runs the `to_arazzo` subcommand to extract an Arazzo 1.1.0 specification from PHP workflow code.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToArazzo(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $inputPath = '';
        $outFile = '';

        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $inputPath = $resolvePath($argv[++$i]);
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $outFile = $resolvePath($argv[++$i]);
            } elseif ($inputPath === '') {
                $inputPath = $resolvePath($argv[$i]);
            }
        }

        if ($inputPath === '' || !file_exists($inputPath)) {
            echo "Error: Input file or directory not found.\n";
            return 1;
        }

        $arazzo = [];
        if (is_dir($inputPath)) {
            if (file_exists("$inputPath/src/ApiWorkflows.php")) {
                $arazzo = \Cdd\ArazzoClient\parse((string) file_get_contents("$inputPath/src/ApiWorkflows.php"));
            } elseif (file_exists("$inputPath/src/arazzo_cli.php")) {
                $arazzo = \Cdd\ArazzoCli\parse((string) file_get_contents("$inputPath/src/arazzo_cli.php"));
            } elseif (file_exists("$inputPath/src/WorkflowEngine.php")) {
                $arazzo = \Cdd\ArazzoServer\parse((string) file_get_contents("$inputPath/src/WorkflowEngine.php"));
            } elseif (file_exists("$inputPath/tests/Workflows/ArazzoWorkflowTest.php")) {
                $arazzo = \Cdd\ArazzoServer\parse((string) file_get_contents("$inputPath/tests/Workflows/ArazzoWorkflowTest.php"));
            }
        } else {
            $raw = (string) file_get_contents($inputPath);
            if (str_contains($inputPath, 'arazzo_cli.php')) {
                $arazzo = \Cdd\ArazzoCli\parse($raw);
            } elseif (str_contains($inputPath, 'WorkflowTest') || str_contains($inputPath, 'WorkflowEngine')) {
                $arazzo = \Cdd\ArazzoServer\parse($raw);
            } else {
                $arazzo = \Cdd\ArazzoClient\parse($raw);
            }
        }

        $outStr = \Cdd\Arazzo\emit($arazzo) . "\n";
        if ($outFile !== '') {
            file_put_contents($outFile, $outStr);
            echo "Emitted Arazzo to $outFile\n";
        } else {
            echo $outStr;
        }
        return 0;
    }

    /**
     * Runs the CLI application.
     *
     * @param array<int, string> $argv The command line arguments
     * @return int The exit status code (0 for success, non-zero for failure)
     */
    public static function run(array $argv): int
    {
        $originalPwd = (string) (getenv('PWD') ?: getcwd());
        $resolvePath = static function (string $path) use ($originalPwd): string {
            if ($path === '' || $path[0] === '/') {
                return $path;
            }
            return $originalPwd . '/' . $path;
        };

        foreach ($_ENV as $k => $v) {
            if (strpos((string) $k, 'CDD_') === 0) {
                $argName = '--' . strtolower(str_replace('_', '-', substr((string) $k, 4)));
                if (!in_array($argName, $argv, true)) {
                    $argv[] = $argName;
                    $argv[] = (string) $v;
                }
            }
        }
        $command = $argv[1] ?? '';

        if ($command === '--version' || $command === '-v') {
            echo "0.0.4
";
            return 0;
        }

        if ($command === '--help' || $command === '-h' || $command === '') {
            return self::printHelp('');
        }

        if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
            $helpExit = self::printHelp($command, $argv[2] ?? '');
            if ($helpExit === 0) {
                return 0;
            }
        }

        return match ($command) {
            'mcp' => self::runMcpServer(),
            'serve_json_rpc' => self::runServeJsonRpc($argv),
            'sync' => self::runSync($argv, $resolvePath),
            'to_openapi' => self::runToOpenApi($argv, $resolvePath),
            'from_openapi', 'emit' => self::runFromOpenApi($argv, $resolvePath),
            'from_arazzo' => self::runFromArazzo($argv, $resolvePath),
            'to_arazzo' => self::runToArazzo($argv, $resolvePath),
            'to_docs_json' => self::runToDocsJson($argv, $resolvePath),
            default => (static function () use ($command): int {
                fwrite(STDERR, "Error: Unknown or incomplete command: $command
");
                return 1;
            })(),
        };
    }
}
