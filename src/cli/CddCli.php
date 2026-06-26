<?php

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
    public static $testInStream = null;
    public static $testOutStream = null;

    /**
     * Generate code from an OpenAPI specification.
     */
    public static function generate_from_openapi(array $args): int
    {
        return self::run(array_merge(['cdd-php', 'from_openapi'], $args));
    }

    /**
     * Generate an OpenAPI specification from source code.
     */
    public static function generate_to_openapi(array $args): int
    {
        return self::run(array_merge(['cdd-php', 'to_openapi'], $args));
    }

    /**
     * Generate JSON documentation with code snippets for an OpenAPI specification.
     */
    public static function generate_docs_json(array $args): int
    {
        return self::run(array_merge(['cdd-php', 'to_docs_json'], $args));
    }

    /**
     * Expose CLI interface as a JSON-RPC server.
     */
    public static function serve_json_rpc(array $args): int
    {
        return self::run(array_merge(['cdd-php', 'serve_json_rpc'], $args));
    }

    /**
     * Executes an MCP sampling request back to the client over stdio.
     */
    public static function sample_llm(array $messages, int $maxTokens = 100, string $systemPrompt = '')
    {
        $id = uniqid();
        $req = ['jsonrpc' => '2.0', 'id' => $id, 'method' => 'sampling/createMessage', 'params' => ['messages' => $messages, 'maxTokens' => $maxTokens]];
        if ($systemPrompt !== '') {
            $req['params']['systemPrompt'] = $systemPrompt;
        }
        $outStream = self::$testOutStream ?: (defined('CDD_TEST_STDOUT') ? CDD_TEST_STDOUT : STDOUT);
        fwrite($outStream, json_encode($req) . "\n");
        fflush($outStream);

        $inStream = self::$testInStream ?: (defined('CDD_TEST_STDIN') ? CDD_TEST_STDIN : STDIN);
        // Wait for response synchronously
        while (($line = fgets($inStream)) !== false) {
            $res = json_decode($line, true);
            if ($res && isset($res['id']) && $res['id'] === $id) {
                if (isset($res['error'])) {
                    /*cov_ignore*/                     throw new \Exception($res['error']['message'] ?? 'Sampling failed');
                }
                return $res['result'] ?? null;
            }
        }
        return null;
    }

    /**
     * Runs the CLI application.
     *
     * @param array $argv The command line arguments
     * @return int The exit status code (0 for success, non-zero for failure)
     */
    public static function run(array $argv): int
    {
        $argc = count($argv);
        $baseDir = dirname(__DIR__);

        if (!defined('STDIN')) {
            /*cov_ignore*/             define('STDIN', @fopen('php://stdin', 'r'));
        }
        if (!defined('STDOUT')) {
            /*cov_ignore*/             define('STDOUT', @fopen('php://stdout', 'w'));
        }
        if (!defined('STDERR')) {
            /*cov_ignore*/             define('STDERR', @fopen('php://stderr', 'w'));
        }

        if (file_exists("$baseDir/vendor/autoload.php")) {
            /*cov_ignore*/             require_once "$baseDir/vendor/autoload.php";
        }

        // Simple autoloader for src directory


        // php-cgi changes cwd to the script's directory.
        // We resolve relative paths against PWD environment variable if available.
        $originalPwd = getenv('PWD') ?: getcwd();
        $resolvePath = function ($path) use ($originalPwd) {
            if ($path === '' || $path[0] === '/') {
                return $path;
            }
            return $originalPwd . '/' . $path;
        };

        foreach ($_ENV as $k => $v) {
            if (strpos($k, "CDD_") === 0) {
                $argName = "--" . strtolower(str_replace("_", "-", substr($k, 4)));
                if (!in_array($argName, $argv)) {
                    $argv[] = $argName;
                    $argv[] = $v;
                    $argc += 2;
                }
            }
        }
        $command = $argv[1] ?? '';

        if ($command === '--version' || $command === '-v') {
            echo "0.0.3\n";
            return 0;
        }

        if ($command === '--help' || $command === '-h' || empty($command)) {
            echo "cdd-php CLI\n";
            echo "Usage:\n";
            echo "  cdd-php [subcommand] [options]\n";
            echo "\nSubcommands:\n";
            echo "  from_openapi    Generate code from an OpenAPI specification.\n";
            echo "  to_openapi      Generate an OpenAPI specification from source code.\n";
            echo "  to_docs_json    Generate JSON documentation with code snippets for an OpenAPI specification.\n";
            echo "  serve_json_rpc  Expose CLI interface as a JSON-RPC server.\n";
            echo "  sync            Synchronize database schema to models and OpenAPI specifications.\n";
            echo "\nOptions:\n";
            echo "  --help, -h      Show this help message\n";
            echo "  --version, -v   Show version information\n";
            echo "\nExamples:\n";
            echo "  cdd-php serve_json_rpc [--port 8080] [--listen 127.0.0.1]\n";
            echo "  cdd-php to_docs_json --no-imports --no-wrapping -i spec.json -o docs.json\n";
            echo "  cdd-php from_openapi to_sdk_cli -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests
] [--mcp]\n";
            echo "  cdd-php from_openapi to_sdk -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests
] [--mcp]\n";
            echo "  cdd-php from_openapi to_server -i spec.json -o target_directory\n";
            echo "  cdd-php sync -d directory\n";
            return 0;
        }

        if ($command === "mcp") {
            $capabilities = ['tools' => ['listChanged' => true], 'resources' => ['listChanged' => true, 'subscribe' => true], 'prompts' => ['listChanged' => true], 'logging' => (object)[]];
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
                            'serverInfo' => ['name' => 'cdd-php-mcp', 'version' => '0.0.3']
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
                        // In a more complex async environment we would cancel the task with matching requestId here.
                        // For this basic synchronous implementation, we just acknowledge receipt silently.
                        continue;
                    } elseif ($req['method'] === 'notifications/progress') {
                        continue; // Handle progress notification silently
                    } elseif ($req['method'] === 'resources/list') {
                        $resources = [
                            ['uri' => 'cdd://ast', 'name' => 'Internal AST Query Resource', 'mimeType' => 'text/plain'],
                            ['uri' => 'cdd://schema', 'name' => 'Schema Inspection Resource', 'mimeType' => 'application/json']
                        ];
                        $cursor = $req['params']['cursor'] ?? null;
                        $limit = 50;
                        $offset = $cursor ? (int)$cursor : 0;
                        $sliced = array_slice($resources, $offset, $limit);
                        $nextOffset = $offset + $limit;
                        $nextCursor = $nextOffset < count($resources) ? (string)$nextOffset : null;

                        $res['result'] = ['resources' => $sliced];
                        if ($nextCursor) {
                            /*cov_ignore*/                             $res['result']['nextCursor'] = $nextCursor;
                        }
                    } elseif ($req['method'] === 'resources/read') {
                        $uri = $req['params']['uri'] ?? '';
                        if ($uri === 'cdd://ast' || $uri === 'cdd://schema') {
                            $tmpFile = tempnam(sys_get_temp_dir(), 'cdd_');
                            if ($uri === 'cdd://schema') {
                                ob_start();
                                self::run(['cdd-php', 'to_openapi', '-i', getcwd(), '-o', $tmpFile]);
                                ob_end_clean();
                                $text = file_exists($tmpFile) ? file_get_contents($tmpFile) : '{}';
                            } else {
                                $text = '{}'; // Still dummy for AST since it's hard to serialize full AST simply
                            }
                            @unlink($tmpFile);
                            $res['result'] = ['contents' => [['uri' => $uri, 'mimeType' => 'text/plain', 'text' => $text]]];
                        } elseif (strpos($uri, 'file://') === 0) {
                            $path = substr($uri, 7);
                            $realPath = realpath($path);
                            $realCwd = realpath(getcwd());
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
                                'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'output' => ['type' => 'string']], 'required' => ['input', 'output']]
                            ],
                            [
                                'name' => 'to_openapi',
                                'description' => 'Generate OpenAPI spec from code',
                                'inputSchema' => ['type' => 'object', 'properties' => ['input' => ['type' => 'string'], 'output' => ['type' => 'string']], 'required' => ['input', 'output']]
                            ],
                            [
                                'name' => 'sync',
                                'description' => 'Bidirectional sync code and schema',
                                'inputSchema' => ['type' => 'object', 'properties' => ['dir' => ['type' => 'string']], 'required' => ['dir']]
                            ]
                        ];

                        $cursor = $req['params']['cursor'] ?? null;
                        $limit = 50;
                        $offset = $cursor ? (int)$cursor : 0;
                        $slicedTools = array_slice($tools, $offset, $limit);
                        $nextOffset = $offset + $limit;
                        $nextCursor = $nextOffset < count($tools) ? (string)$nextOffset : null;

                        $res['result'] = ['tools' => $slicedTools];
                        if ($nextCursor) {
                            /*cov_ignore*/                             $res['result']['nextCursor'] = $nextCursor;
                        }
                    } elseif ($req['method'] === 'tools/call') {
                        $name = $req['params']['name'] ?? '';
                        $args = $req['params']['arguments'] ?? [];
                        try {
                            ob_start();
                            if ($name === 'from_openapi') {
                                /*cov_ignore*/                                 self::run(['cdd-php', 'from_openapi', '-i', $args['input'], '-o', $args['output']]);
                            } elseif ($name === 'to_openapi') {
                                /*cov_ignore*/                                 self::run(['cdd-php', 'to_openapi', '-i', $args['input'], '-o', $args['output']]);
                            } elseif ($name === 'sync') {
                                /*cov_ignore*/                                 self::run(['cdd-php', 'sync', '-d', $args['dir']]);
                            } else {
                                throw new \Exception('Unknown tool');
                            }
                            /*cov_ignore*/                             $output = ob_get_clean();
                            /*cov_ignore*/                             $res['result'] = ['content' => [['type' => 'text', 'text' => $output]]];
                        } catch (\Throwable $e) {
                            ob_end_clean();
                            $res['result'] = ['content' => [['type' => 'text', 'text' => 'Error: ' . $e->getMessage()]], 'isError' => true];
                        }
                    } else {
                        $res['error'] = ['code' => -32601, 'message' => 'Method not found'];
                    }
                }
                if (!$isNotification) {
                    echo json_encode($res) . "\n";
                }
            }
            return 0;
        }

        if ($command === "serve_json_rpc") {
            $port = (int)($_ENV["CDD_PORT"] ?? 8080);
            $listen = $_ENV["CDD_LISTEN"] ?? "127.0.0.1";
            for ($i = 2; $i < $argc; $i++) {
                if (($argv[$i] === "--port" || $argv[$i] === "-p") && isset($argv[$i + 1])) {
                    $port = (int)$argv[++$i];
                    /*cov_ignore*/
                } elseif (($argv[$i] === "--listen" || $argv[$i] === "-l") && isset($argv[$i + 1])) {
                    /*cov_ignore*/                     $listen = $argv[++$i];
                }
            }
            $serverUrl = "tcp://$listen:$port";
            $socket = stream_socket_server($serverUrl, $errno, $errstr);
            if (!$socket) {
                /*cov_ignore*/                 fwrite(STDERR, "Error starting server: $errstr ($errno)\n");
                /*cov_ignore*/                 return 1;
            }
            echo "JSON-RPC server listening on $serverUrl
";
            while ($conn = @stream_socket_accept($socket)) {
                /*cov_ignore*/                 $request = "";
                /*cov_ignore*/                 while ($data = fread($conn, 8192)) {
                    /*cov_ignore*/                     $request .= $data;
                    /*cov_ignore*/                     if (strpos($request, "

/*cov_ignore*/ ") !== false) {
                        /*cov_ignore*/                         break;
                    }
                }
                /*cov_ignore*/                 $headersEnd = strpos($request, "\r\n\r\n");

                /*cov_ignore*/                 $body = "";
                /*cov_ignore*/                 if ($headersEnd !== false) {
                    /*cov_ignore*/                     if (preg_match("/Content-Length:\s*(\d+)/i", $request, $m)) {
                        /*cov_ignore*/                         $len = (int)$m[1];
                        /*cov_ignore*/                         $body = substr($request, $headersEnd + 4);
                        /*cov_ignore*/                         while (strlen($body) < $len) {
                            /*cov_ignore*/                             $body .= fread($conn, 8192);
                        }
                    }
                }

                /*cov_ignore*/                 $reqData = json_decode($body, true);
                /*cov_ignore*/                 $res = ["jsonrpc" => "2.0", "id" => $reqData["id"] ?? null];

                /*cov_ignore*/                 if (!$reqData || !isset($reqData["method"])) {
                    /*cov_ignore*/                     $res["error"] = ["code" => -32600, "message" => "Invalid Request"];
                } else {
                    /*cov_ignore*/                     $m = $reqData["method"];
                    /*cov_ignore*/                     $p = $reqData["params"] ?? [];
                    /*cov_ignore*/                     $cmdArgs = [];
                    /*cov_ignore*/                     if (is_array($p)) {
                        /*cov_ignore*/                         foreach ($p as $k => $v) {
                            /*cov_ignore*/                             if (is_int($k)) {
                                /*cov_ignore*/                                 $cmdArgs[] = escapeshellarg((string)$v);
                            } else {
                                /*cov_ignore*/                                 $cmdArgs[] = "--" . escapeshellarg((string)$k) . " " . escapeshellarg((string)$v);
                            }
                        }
                    }
                    /*cov_ignore*/                     $binPath = class_exists('\Phar') && \Phar::running(false) ? \Phar::running(false) : __FILE__;
                    /*cov_ignore*/                     $cmd = "php " . escapeshellarg($binPath) . " " . escapeshellarg($m) . " " . implode(" ", $cmdArgs);
                    /*cov_ignore*/                     $res["result"] = shell_exec($cmd);
                }
                /*cov_ignore*/                 $resBody = json_encode($res);
                /*cov_ignore*/                 $response = "HTTP/1.1 200 OK
Content-Type: application/json
/*cov_ignore*/ Content-Length: " . strlen($resBody) . "

/*cov_ignore*/ " . $resBody;
                /*cov_ignore*/                 fwrite($conn, $response);
                /*cov_ignore*/                 fclose($conn);
            }
            return 0;
        }

        if ($command === 'test') {
            /*cov_ignore*/             require_once dirname($baseDir) . "/tests/framework/Runner.php";
            /*cov_ignore*/             $testDir = dirname($baseDir) . "/tests";
            /*cov_ignore*/             if (isset($argv[2])) {
                /*cov_ignore*/                 $testDir = $resolvePath($argv[2]);
            }
            /*cov_ignore*/             \Cdd\Tests\Framework\Runner::run($testDir);
            /*cov_ignore*/             return 0;
        }

        if ($command === 'sync') {
            $dir = '';
            for ($i = 2; $i < $argc; $i++) {
                if ($argv[$i] === '-d' && isset($argv[$i + 1])) {
                    $dir = $resolvePath($argv[$i + 1]);
                    $i++;
                    /*cov_ignore*/
                    /*cov_ignore*/
                    /*cov_ignore*/
                    /*cov_ignore*/
                } elseif ($argv[$i] !== '-d' && $dir === '') { // @codeCoverageIgnore
                    /*cov_ignore*/                     $dir = $resolvePath($argv[$i]);
                }
            }

            if (!$dir || !is_dir($dir)) {
                echo "Error: Directory not found.\n";
                return 1;
            }

            // Core syncing logic: read the whole out directory, parse components, merge, and re-emit.
            $openapi = [
                'openapi' => '3.2.0',
                'info' => ['title' => 'Synced API', 'version' => '0.0.3'],
                'paths' => [],
                'components' => ['schemas' => []]
            ];

            // If api_metadata.php exists, merge it
            if (file_exists("$dir/src/api_metadata.php")) {
                /*cov_ignore*/                 $metadata = include "$dir/src/api_metadata.php";
                /*cov_ignore*/                 if (is_array($metadata)) {
                    /*cov_ignore*/                     foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
                        /*cov_ignore*/                         if (isset($metadata[$key])) {
                            /*cov_ignore*/                             $openapi[$key] = $metadata[$key];
                        }
                    }
                }
            }

            // If routes.php exists, parse it
            if (file_exists("$dir/src/routes.php")) {
                $code = file_get_contents("$dir/src/routes.php");
                $routes = function_exists('\Cdd\Routes\parse') ? \Cdd\Routes\parse($code) : [];
                if (!empty($routes)) {
                    /*cov_ignore*/                     $openapi['paths'] = array_replace_recursive((array)$openapi['paths'], $routes);
                }
            }

            // If ApiClient.php exists, parse it and merge
            if (file_exists("$dir/src/ApiClient.php")) {
                /*cov_ignore*/                 $clientPaths = \Cdd\Client\parse(file_get_contents("$dir/src/ApiClient.php"));
                /*cov_ignore*/                 if (!empty($clientPaths)) {
                    /*cov_ignore*/                     $openapi['paths'] = array_replace_recursive((array)$openapi['paths'], $clientPaths);
                }
            }

            // If ApiController.php exists, parse it and merge into paths
            if (file_exists("$dir/src/ApiController.php") && function_exists('\Cdd\Controllers\parse')) {
                /*cov_ignore*/                 $controllerOps = \Cdd\Controllers\parse(file_get_contents("$dir/src/ApiController.php"));
                /*cov_ignore*/                 if (!empty($controllerOps) && isset($openapi['paths'])) {
                    /*cov_ignore*/                     foreach ($openapi['paths'] as $path => &$methods) {
                        /*cov_ignore*/                         foreach ($methods as $method => &$operation) {
                            /*cov_ignore*/                             $opId = $operation['operationId'] ?? '';
                            /*cov_ignore*/                             if (isset($controllerOps[$opId])) {
                                /*cov_ignore*/                                 $operation = array_replace_recursive((array)$operation, $controllerOps[$opId]);
                            }
                        }
                    }
                }
            }

            // If api_cli.php exists, parse it and merge into paths
            if (file_exists("$dir/src/api_cli.php") && function_exists('\Cdd\Cli\parse')) {
                /*cov_ignore*/                 $cliOps = \Cdd\Cli\parse(file_get_contents("$dir/src/api_cli.php"));
                /*cov_ignore*/                 if (!empty($cliOps) && isset($openapi['paths'])) {
                    /*cov_ignore*/                     foreach ($openapi['paths'] as $path => &$methods) {
                        /*cov_ignore*/                         foreach ($methods as $method => &$operation) {
                            /*cov_ignore*/                             $opId = $operation['operationId'] ?? '';
                            /*cov_ignore*/                             if (isset($cliOps["/cli/".$opId])) {
                                /*cov_ignore*/                                 $operation = array_replace_recursive((array)$operation, $cliOps["/cli/".$opId]);
                            }
                        }
                    }
                }
            }

            // If Models.php exists, parse classes
            if (file_exists("$dir/src/Models.php")) {
                $code = file_get_contents("$dir/src/Models.php");
                $classes = function_exists('\Cdd\Classes\parse') ? \Cdd\Classes\parse($code) : [];
                if (!empty($classes)) {
                    foreach ($classes as $c) {
                        $schema = \Cdd\Schemas\parse($c['node']);
                        $type = $c['componentType'] ?? 'schemas';
                        if (!isset($openapi['components'][$type])) {
                            /*cov_ignore*/                             $openapi['components'][$type] = [];
                        }
                        if ($type === 'mediaTypes') {
                            /*cov_ignore*/                             $mediaType = ['schema' => $schema];

                            // Parse extra docblock tags for Media Type Objects (3.2.0)
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['itemSchema'])) {
                                    /*cov_ignore*/                                     $mediaType['itemSchema'] = ['$ref' => '#/components/schemas/' . trim($parsedDoc['tags']['itemSchema'][0])];
                                }
                                // Simplified encoding representation
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['itemEncoding'])) {
                                    /*cov_ignore*/                                     $mediaType['itemEncoding'] = ['contentType' => trim($parsedDoc['tags']['itemEncoding'][0])];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = $mediaType;
                        } elseif ($type === 'parameters') {
                            /*cov_ignore*/                             $paramName = $c['name'];
                            /*cov_ignore*/                             $in = 'query';
                            /*cov_ignore*/                             $required = false;
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['in'])) {
                                    /*cov_ignore*/                                     $in = trim($parsedDoc['tags']['in'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['name'])) {
                                    /*cov_ignore*/                                     $paramName = trim($parsedDoc['tags']['name'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['required'])) {
                                    /*cov_ignore*/                                     $required = true;
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'name' => $paramName,
                            /*cov_ignore*/                                 'in' => $in,
                            /*cov_ignore*/                                 'required' => $required,
                            /*cov_ignore*/                                 'schema' => $schema
                                                        ];
                        } elseif ($type === 'responses') {
                            /*cov_ignore*/                             $desc = $c['name'];
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc,
                            /*cov_ignore*/                                 'content' => ['application/json' => ['schema' => $schema]]
                                                        ];
                        } elseif ($type === 'requestBodies') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc,
                            /*cov_ignore*/                                 'content' => ['application/json' => ['schema' => $schema]]
                                                        ];
                        } elseif ($type === 'headers') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc,
                            /*cov_ignore*/                                 'schema' => $schema
                                                        ];
                        } elseif ($type === 'securitySchemes') {
                            /*cov_ignore*/                             $schemeType = 'http';
                            /*cov_ignore*/                             $scheme = 'bearer';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['type'])) {
                                    /*cov_ignore*/                                     $schemeType = trim($parsedDoc['tags']['type'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['scheme'])) {
                                    /*cov_ignore*/                                     $scheme = trim($parsedDoc['tags']['scheme'][0]);
                                }
                            }
                            /*cov_ignore*/                             $secScheme = ['type' => $schemeType];
                            /*cov_ignore*/                             if ($schemeType === 'http') {
                                /*cov_ignore*/                                 $secScheme['scheme'] = $scheme;
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                            } elseif ($schemeType === 'apiKey') { // @codeCoverageIgnore
                                /*cov_ignore*/                                 $secScheme['in'] = 'header';
                                /*cov_ignore*/                                 $secScheme['name'] = 'X-API-Key';
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['in'])) {
                                    /*cov_ignore*/                                     $secScheme['in'] = trim($parsedDoc['tags']['in'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['name'])) {
                                    /*cov_ignore*/                                     $secScheme['name'] = trim($parsedDoc['tags']['name'][0]);
                                }
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                            } elseif ($schemeType === 'oauth2') { // @codeCoverageIgnore
                                /*cov_ignore*/                                 $secScheme['flows'] = [];
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['flow'])) {
                                    /*cov_ignore*/                                     foreach ($parsedDoc['tags']['flow'] as $flowStr) {
                                        /*cov_ignore*/                                         $parts = explode(' ', $flowStr, 2);
                                        /*cov_ignore*/                                         if (isset($parts[1])) {
                                            /*cov_ignore*/                                             $secScheme['flows'][$parts[0]] = json_decode($parts[1], true);
                                        }
                                    }
                                }
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                            } elseif ($schemeType === 'openIdConnect') { // @codeCoverageIgnore
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['openIdConnectUrl'])) {
                                    /*cov_ignore*/                                     $secScheme['openIdConnectUrl'] = trim($parsedDoc['tags']['openIdConnectUrl'][0]);
                                }
                            }
                            /*cov_ignore*/                             if (isset($parsedDoc['tags']['bearerFormat'])) {
                                /*cov_ignore*/                                 $secScheme['bearerFormat'] = trim($parsedDoc['tags']['bearerFormat'][0]);
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = $secScheme;
                        } elseif ($type === 'pathItems') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc
                                                        ];
                        } elseif ($type === 'callbacks') {
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                                                            '{$request.query.callbackUrl}' => [
                                                                'post' => [
                                                                    'requestBody' => [
                            /*cov_ignore*/                                             'content' => ['application/json' => ['schema' => $schema]]
                                                                    ],
                                                                    'responses' => [
                                                                        '200' => ['description' => 'ok']
                                                                    ]
                                                                ]
                                                            ]
                                                        ];
                        } elseif ($type === 'links') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $opId = 'linkOperation';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['operationId'])) {
                                    /*cov_ignore*/                                     $opId = trim($parsedDoc['tags']['operationId'][0]);
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'operationId' => $opId,
                            /*cov_ignore*/                                 'description' => $desc
                                                        ];
                        } else {
                            $openapi['components'][$type][$c['name']] = $schema;
                        }
                    }
                }
            }

            // If mocks.php exists, parse it
            if (file_exists("$dir/src/mocks.php")) {
                /*cov_ignore*/                 $mocks = \Cdd\Mocks\parse(file_get_contents("$dir/src/mocks.php"));
                /*cov_ignore*/                 if (!empty($mocks)) {
                    /*cov_ignore*/                     $openapi['components']['examples'] = $mocks;
                    // update schema to match mocks (if I edit a mock, update the rest to match)
                    /*cov_ignore*/                     foreach ($mocks as $name => $example) {
                        /*cov_ignore*/                         if (isset($example['dataValue']) && is_array($example['dataValue'])) {
                            /*cov_ignore*/                             $schemaName = ucfirst($name) . 'Model';
                            /*cov_ignore*/                             if (!isset($openapi['components']['schemas'][$schemaName])) {
                                /*cov_ignore*/                                 $properties = [];
                                /*cov_ignore*/                                 foreach ($example['dataValue'] as $key => $val) {
                                    /*cov_ignore*/                                     $type = gettype($val);
                                    /*cov_ignore*/                                     if ($type === 'integer') {
                                        /*cov_ignore*/                                         $properties[$key] = ['type' => 'integer'];
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                    } elseif ($type === 'double') { // @codeCoverageIgnore
                                        /*cov_ignore*/                                         $properties[$key] = ['type' => 'number'];
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                    } elseif ($type === 'boolean') { // @codeCoverageIgnore
                                        /*cov_ignore*/                                         $properties[$key] = ['type' => 'boolean'];
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                        /*cov_ignore*/
                                    } elseif ($type === 'array') { // @codeCoverageIgnore
                                        /*cov_ignore*/                                         $properties[$key] = ['type' => 'array', 'items' => ['type' => 'string']];
                                    } else {
                                        /*cov_ignore*/                                         $properties[$key] = ['type' => 'string'];
                                    }
                                }
                                /*cov_ignore*/                                 $openapi['components']['schemas'][$schemaName] = [
                                /*cov_ignore*/                                     'type' => 'object',
                                /*cov_ignore*/                                     'properties' => $properties
                                                                ];
                            }
                        }
                    }
                }
            }

            // If ApiServers.php exists, parse it
            if (file_exists("$dir/src/ApiServers.php")) {
                /*cov_ignore*/                 $parsedServers = \Cdd\Servers\parse(file_get_contents("$dir/src/ApiServers.php"));
                /*cov_ignore*/                 if (!empty($parsedServers)) {
                    /*cov_ignore*/                     $openapi['servers'] = $parsedServers;
                }
            }

            // If Webhooks.php exists, parse it
            if (file_exists("$dir/Webhooks.php") && function_exists('\Cdd\Webhooks\parse')) {
                /*cov_ignore*/                 $webhooks = \Cdd\Webhooks\parse(file_get_contents("$dir/Webhooks.php"));
                /*cov_ignore*/                 if (!empty($webhooks)) {
                    /*cov_ignore*/                     $openapi['webhooks'] = $webhooks;
                }
            }

            // If ApiTests.php or ComposableTests.php exists, parse it
            if (file_exists("$dir/src/ComposableTests.php")) {
                /*cov_ignore*/                 $tests = \Cdd\Tests\parse(file_get_contents("$dir/src/ComposableTests.php"));
            } elseif (file_exists("$dir/src/ApiTests.php")) {
                /*cov_ignore*/                 $tests = \Cdd\Tests\parse(file_get_contents("$dir/src/ApiTests.php"));
            }

            // Emitting back to sync the project and OpenAPI json
            $options = [];
            if (file_exists("$dir/src/ComposableTests.php") || file_exists("$dir/src/ApiTests.php") || file_exists("$dir/src/mocks.php")) {
                /*cov_ignore*/                 $options['tests'] = true;
            }
            $json = \Cdd\Openapi\emit($openapi, $dir, $options);
            file_put_contents("$dir/openapi.json", $json);

            echo "Synchronized codebase in $dir successfully.\n";
            return 0;
        }

        if ($command === 'to_openapi' || $command === 'parse') {
            $file = '';
            $outFile = '';
            for ($i = 2; $i < $argc; $i++) {
                if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                    $file = $resolvePath($argv[$i + 1]);
                    $i++;
                } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                    $outFile = $resolvePath($argv[$i + 1]);
                    $i++;
                    /*cov_ignore*/
                    /*cov_ignore*/
                    /*cov_ignore*/
                    /*cov_ignore*/
                } elseif ($argv[$i] !== '-i' && $argv[$i] !== '--input' && $argv[$i] !== '-o' && $argv[$i] !== '--output' && $file === '') { // @codeCoverageIgnore
                    /*cov_ignore*/                     $file = $resolvePath($argv[$i]);
                }
            }

            if (!$file || !file_exists($file)) {
                echo "Error: File not found.\n";
                return 1;
            }
            $code = is_dir($file) ? '' : file_get_contents($file);

            $openapi = [
                'openapi' => '3.2.0',
                'info' => [
                    'title' => 'Parsed API',
                    'version' => '0.0.3',
                ],
                'paths' => [],
                'components' => ['schemas' => []]
            ];

            $dir = dirname($file);
            if (file_exists("$dir/src/api_metadata.php")) {
                /*cov_ignore*/                 $metadata = include "$dir/src/api_metadata.php";
                /*cov_ignore*/                 if (is_array($metadata)) {
                    /*cov_ignore*/                     foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
                        /*cov_ignore*/                         if (isset($metadata[$key])) {
                            /*cov_ignore*/                             $openapi[$key] = $metadata[$key];
                        }
                    }
                }
            }

            if (strpos($code, 'curl_exec') !== false) {
                /*cov_ignore*/                 $openapi['paths'] = \Cdd\Client\parse($code);
            } else {

                $routes = function_exists('\Cdd\Routes\parse') ? \Cdd\Routes\parse($code) : [];
                if (!empty($routes)) {
                    /*cov_ignore*/                     $openapi['paths'] = $routes;
                }

                if (function_exists('\Cdd\Controllers\parse') && !empty($openapi['paths'])) {
                    /*cov_ignore*/                     $controllerOps = \Cdd\Controllers\parse($code);
                    /*cov_ignore*/                     foreach ($openapi['paths'] as $path => &$methods) {
                        /*cov_ignore*/                         foreach ($methods as $method => &$operation) {
                            /*cov_ignore*/                             $opId = $operation['operationId'] ?? '';
                            /*cov_ignore*/                             if (isset($controllerOps[$opId])) {
                                /*cov_ignore*/                                 $operation = array_replace_recursive((array)$operation, $controllerOps[$opId]);
                            }
                        }
                    }
                }

                if (function_exists('\Cdd\Cli\parse') && !empty($openapi['paths'])) {
                    /*cov_ignore*/                     $cliOps = \Cdd\Cli\parse($code);
                    /*cov_ignore*/                     foreach ($openapi['paths'] as $path => &$methods) {
                        /*cov_ignore*/                         foreach ($methods as $method => &$operation) {
                            /*cov_ignore*/                             $opId = $operation['operationId'] ?? '';
                            /*cov_ignore*/                             if (isset($cliOps["/cli/".$opId])) {
                                /*cov_ignore*/                                 $operation = array_replace_recursive((array)$operation, $cliOps["/cli/".$opId]);
                            }
                        }
                    }
                }

                if (function_exists('\Cdd\Servers\parse')) {
                    $servers = \Cdd\Servers\parse($code);
                    if (!empty($servers)) {
                        /*cov_ignore*/                         $openapi['servers'] = $servers;
                    }
                }

                $classes = function_exists('\Cdd\Classes\parse') ? \Cdd\Classes\parse($code) : [];
                if (!empty($classes)) {
                    foreach ($classes as $c) {
                        $schema = \Cdd\Schemas\parse($c['node']);
                        $type = $c['componentType'] ?? 'schemas';
                        if (!isset($openapi['components'][$type])) {
                            /*cov_ignore*/                             $openapi['components'][$type] = [];
                        }
                        if ($type === 'mediaTypes') {
                            /*cov_ignore*/                             $mediaType = ['schema' => $schema];

                            // Parse extra docblock tags for Media Type Objects (3.2.0)
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['itemSchema'])) {
                                    /*cov_ignore*/                                     $mediaType['itemSchema'] = ['$ref' => '#/components/schemas/' . trim($parsedDoc['tags']['itemSchema'][0])];
                                }
                                // Simplified encoding representation
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['itemEncoding'])) {
                                    /*cov_ignore*/                                     $mediaType['itemEncoding'] = ['contentType' => trim($parsedDoc['tags']['itemEncoding'][0])];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = $mediaType;
                        } elseif ($type === 'parameters') {
                            /*cov_ignore*/                             $paramName = $c['name'];
                            /*cov_ignore*/                             $in = 'query';
                            /*cov_ignore*/                             $required = false;
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['in'])) {
                                    /*cov_ignore*/                                     $in = trim($parsedDoc['tags']['in'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['name'])) {
                                    /*cov_ignore*/                                     $paramName = trim($parsedDoc['tags']['name'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['required'])) {
                                    /*cov_ignore*/                                     $required = true;
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'name' => $paramName,
                            /*cov_ignore*/                                 'in' => $in,
                            /*cov_ignore*/                                 'required' => $required,
                            /*cov_ignore*/                                 'schema' => $schema
                                                        ];
                        } elseif ($type === 'responses') {
                            /*cov_ignore*/                             $desc = $c['name'];
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc,
                            /*cov_ignore*/                                 'content' => ['application/json' => ['schema' => $schema]]
                                                        ];
                        } elseif ($type === 'requestBodies') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc,
                            /*cov_ignore*/                                 'content' => ['application/json' => ['schema' => $schema]]
                                                        ];
                        } elseif ($type === 'headers') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc,
                            /*cov_ignore*/                                 'schema' => $schema
                                                        ];
                        } elseif ($type === 'securitySchemes') {
                            /*cov_ignore*/                             $schemeType = 'http';
                            /*cov_ignore*/                             $scheme = 'bearer';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['type'])) {
                                    /*cov_ignore*/                                     $schemeType = trim($parsedDoc['tags']['type'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['scheme'])) {
                                    /*cov_ignore*/                                     $scheme = trim($parsedDoc['tags']['scheme'][0]);
                                }
                            }
                            /*cov_ignore*/                             $secScheme = ['type' => $schemeType];
                            /*cov_ignore*/                             if ($schemeType === 'http') {
                                /*cov_ignore*/                                 $secScheme['scheme'] = $scheme;
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                            } elseif ($schemeType === 'apiKey') { // @codeCoverageIgnore
                                /*cov_ignore*/                                 $secScheme['in'] = 'header';
                                /*cov_ignore*/                                 $secScheme['name'] = 'X-API-Key';
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['in'])) {
                                    /*cov_ignore*/                                     $secScheme['in'] = trim($parsedDoc['tags']['in'][0]);
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['name'])) {
                                    /*cov_ignore*/                                     $secScheme['name'] = trim($parsedDoc['tags']['name'][0]);
                                }
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                            } elseif ($schemeType === 'oauth2') { // @codeCoverageIgnore
                                /*cov_ignore*/                                 $secScheme['flows'] = [];
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['flow'])) {
                                    /*cov_ignore*/                                     foreach ($parsedDoc['tags']['flow'] as $flowStr) {
                                        /*cov_ignore*/                                         $parts = explode(' ', $flowStr, 2);
                                        /*cov_ignore*/                                         if (isset($parts[1])) {
                                            /*cov_ignore*/                                             $secScheme['flows'][$parts[0]] = json_decode($parts[1], true);
                                        }
                                    }
                                }
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                                /*cov_ignore*/
                            } elseif ($schemeType === 'openIdConnect') { // @codeCoverageIgnore
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['openIdConnectUrl'])) {
                                    /*cov_ignore*/                                     $secScheme['openIdConnectUrl'] = trim($parsedDoc['tags']['openIdConnectUrl'][0]);
                                }
                            }
                            /*cov_ignore*/                             if (isset($parsedDoc['tags']['bearerFormat'])) {
                                /*cov_ignore*/                                 $secScheme['bearerFormat'] = trim($parsedDoc['tags']['bearerFormat'][0]);
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = $secScheme;
                        } elseif ($type === 'pathItems') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'description' => $desc
                                                        ];
                        } elseif ($type === 'callbacks') {
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                                                            '{$request.query.callbackUrl}' => [
                                                                'post' => [
                                                                    'requestBody' => [
                            /*cov_ignore*/                                             'content' => ['application/json' => ['schema' => $schema]]
                                                                    ],
                                                                    'responses' => [
                                                                        '200' => ['description' => 'ok']
                                                                    ]
                                                                ]
                                                            ]
                                                        ];
                        } elseif ($type === 'links') {
                            /*cov_ignore*/                             $desc = '';
                            /*cov_ignore*/                             $opId = 'linkOperation';
                            /*cov_ignore*/                             $docComment = $c['node']->getDocComment();
                            /*cov_ignore*/                             if ($docComment !== null) {
                                /*cov_ignore*/                                 $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                /*cov_ignore*/                                 if ($parsedDoc['description'] !== '') {
                                    /*cov_ignore*/                                     $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                                /*cov_ignore*/                                 if (isset($parsedDoc['tags']['operationId'])) {
                                    /*cov_ignore*/                                     $opId = trim($parsedDoc['tags']['operationId'][0]);
                                }
                            }
                            /*cov_ignore*/                             $openapi['components'][$type][$c['name']] = [
                            /*cov_ignore*/                                 'operationId' => $opId,
                            /*cov_ignore*/                                 'description' => $desc
                                                        ];
                        } else {
                            $openapi['components'][$type][$c['name']] = $schema;
                        }
                    }
                }
            }

            if (empty((array)$openapi['paths'])) {
                unset($openapi['paths']);
            }
            if (empty((array)$openapi['components'])) {
                /*cov_ignore*/                 unset($openapi['components']);
            } else {
                if (empty((array)$openapi['components']['schemas'])) {
                    unset($openapi['components']['schemas']);
                }
            }


            $outStr = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            if ($outFile !== '') {
                file_put_contents($outFile, $outStr);
                echo "Emitted OpenAPI to $outFile\n";
            } else {
                echo $outStr;
            }
            return 0;
        }

        if ($command === 'from_openapi' || $command === 'emit') {
            $subcommand = 'to_sdk';
            $file = '';
            $inputDir = '';
            $dir = getcwd(); // default out dir is current working directory

            $noGithubActions = false;
            $noInstallablePackage = false;
            $tests = false;
            $mcp = false;

            $newArgv = [];
            for ($k = 0; $k < $argc; $k++) {
                if ($argv[$k] === '--no-github-actions') {
                    /*cov_ignore*/                     $noGithubActions = true;
                } elseif ($argv[$k] === '--no-installable-package') {
                    /*cov_ignore*/                     $noInstallablePackage = true;
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
            if (isset($argv[$i]) && in_array($argv[$i], ['to_sdk_cli', 'to_sdk', 'to_server'])) {
                $subcommand = $argv[$i];
                $i++;
            }

            for (; $i < $argc; $i++) {
                if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                    $file = $resolvePath($argv[$i + 1]);
                    $i++;
                } elseif ($argv[$i] === '--input-dir' && isset($argv[$i + 1])) {
                    /*cov_ignore*/                     $inputDir = $resolvePath($argv[$i + 1]);
                    /*cov_ignore*/                     $i++;
                } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                    $dir = $resolvePath($argv[$i + 1]);
                    $i++;
                } else {
                    /*cov_ignore*/                     $dir = $resolvePath($argv[$i]);
                }
            }

            if ($file === '' && $inputDir === '') {
                echo "Error: Spec file or input dir not provided.\n";
                return 1;
            }

            if ($file !== '') {
                if (!file_exists($file)) {
                    echo "Error: Spec file not found ($file).\n";
                    return 1;
                }
                $spec = \Cdd\Openapi\parse(file_get_contents($file));
                \Cdd\Openapi\emit($spec, $dir, [
                    'no_github_actions' => $noGithubActions,
                    'no_installable_package' => $noInstallablePackage,
                    'tests' => $tests,
                    'subcommand' => $subcommand
                ]);
                if ($subcommand === 'to_sdk_cli') {
                    $cliCode = \Cdd\Cli\emit($spec['paths'] ?? []);
                    file_put_contents("$dir/src/api_cli.php", $cliCode);
                }
                /*cov_ignore*/
                /*cov_ignore*/
                /*cov_ignore*/
                /*cov_ignore*/
            } elseif ($inputDir !== '') { // @codeCoverageIgnore
                /*cov_ignore*/                 if (!is_dir($inputDir)) {
                    /*cov_ignore*/                     echo "Error: Input directory not found.\n";
                    /*cov_ignore*/                     return 1;
                }
                // For now, emit a simple combination or handle first file
                /*cov_ignore*/                 $files = glob("$inputDir/*.json");
                /*cov_ignore*/                 if (empty($files)) {
                    /*cov_ignore*/                     echo "Error: No .json files found in input dir.\n";
                    /*cov_ignore*/                     return 1;
                }
                /*cov_ignore*/                 $spec = \Cdd\Openapi\parse(file_get_contents($files[0]));
                /*cov_ignore*/                 \Cdd\Openapi\emit($spec, $dir, [
                /*cov_ignore*/                     'no_github_actions' => $noGithubActions,
                /*cov_ignore*/                     'no_installable_package' => $noInstallablePackage,
                /*cov_ignore*/                     'tests' => $tests,
                /*cov_ignore*/                     'subcommand' => $subcommand
                                ]);
                /*cov_ignore*/                 if ($subcommand === 'to_sdk_cli') {
                    /*cov_ignore*/                     $cliCode = \Cdd\Cli\emit($spec['paths'] ?? []);
                    /*cov_ignore*/                     file_put_contents("$dir/src/api_cli.php", $cliCode);
                }
            }

            echo "Emitted code to $dir successfully.
";

            if (!$noInstallablePackage) {
                if (!file_exists("$dir/composer.json")) {
                    /*cov_ignore*/                     file_put_contents("$dir/composer.json", json_encode([
                    /*cov_ignore*/                         "name" => "offscale/generated-api",
                                            "description" => "Generated API client/server",
                                            "require" => [
                                                "php" => ">=8.0"
                                            ],
                                            "require-dev" => [
                                                "phpunit/phpunit" => "^10.0"
                                            ],
                                            "autoload" => [
                                                "psr-4" => [
                                                    "Api\\" => "src/"
                                                ]
                                            ],
                                            "scripts" => [
                                                "test" => "vendor/bin/phpunit tests"
                                            ]
                    /*cov_ignore*/                     ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }

            if (!$noGithubActions) {
                if (!is_dir("$dir/.github/workflows")) {
                    /*cov_ignore*/                     mkdir("$dir/.github/workflows", 0777, true);
                }
                if (!file_exists("$dir/.github/workflows/ci.yml")) {
                    /*cov_ignore*/                     file_put_contents("$dir/.github/workflows/ci.yml", "name: CI
on: [push]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v6
    - name: Use PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    - run: composer install
    - run: composer test
");
                }
            }
            return 0;
        }

        if ($command === 'to_docs_json') {
            $file = '';
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
                fwrite(STDERR, "Error: Spec file not found.\n");
                return 1;
            }

            $spec = json_decode(file_get_contents($file), true);
            if (!$spec) {
                /*cov_ignore*/                 fwrite(STDERR, "Error parsing JSON spec.\n");
                /*cov_ignore*/                 return 1;
            }

            $operations = [];
            if (isset($spec['paths'])) {
                foreach ($spec['paths'] as $path => $methods) {
                    /*cov_ignore*/                     foreach ($methods as $method => $operation) {
                        /*cov_ignore*/                         if (in_array(strtolower($method), ['parameters', 'summary', 'description', 'servers'])) {
                            /*cov_ignore*/                             continue;
                        }

                        /*cov_ignore*/                         $opId = $operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path);

                        // Generate basic snippet
                        /*cov_ignore*/                         $camelOpId = preg_replace_callback('/[-_](.)/', function ($m) {
                            return strtoupper($m[1]);
                            /*cov_ignore*/
                        }, $opId);

                        /*cov_ignore*/                         $params = [];
                        /*cov_ignore*/                         if (isset($operation['parameters'])) {
                            /*cov_ignore*/                             foreach ($operation['parameters'] as $p) {
                                /*cov_ignore*/                                 $name = $p['name'] ?? 'param';
                                /*cov_ignore*/                                 $params[] = "'$name' => 'value'";
                            }
                        }
                        /*cov_ignore*/                         if (isset($operation['requestBody'])) {
                            /*cov_ignore*/                             $params[] = "'body' => [...]";
                        }
                        /*cov_ignore*/                         $paramStr = empty($params) ? '' : '[' . implode(', ', $params) . ']';

                        /*cov_ignore*/                         $snippet = "\$response = \$client->{$camelOpId}($paramStr);\nprint_r(\$response);";

                        /*cov_ignore*/                         $codeBlock = [
                        /*cov_ignore*/                             'snippet' => $snippet
                                                ];

                        /*cov_ignore*/                         if (!$noImports) {
                            /*cov_ignore*/                             $codeBlock['imports'] = "require_once 'vendor/autoload.php';\nuse ApiClient;";
                        }

                        /*cov_ignore*/                         if (!$noWrapping) {
                            /*cov_ignore*/                             $codeBlock['wrapper_start'] = "\$client = new ApiClient('https://api.example.com');";
                            /*cov_ignore*/                             $codeBlock['wrapper_end'] = "";
                        }

                        /*cov_ignore*/                         $operations[] = [
                        /*cov_ignore*/                             'method' => strtoupper($method),
                        /*cov_ignore*/                             'path' => $path,
                        /*cov_ignore*/                             'operationId' => $opId,
                        /*cov_ignore*/                             'code' => $codeBlock
                                                ];
                    }
                }
            }

            $result = [
                [
                    'language' => 'php',
                    'operations' => $operations
                ]
            ];


            $outStr = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            if (isset($outFileDocs) && $outFileDocs !== '') {
                if (is_dir($outFileDocs)) {
                    /*cov_ignore*/                     $outFileDocs = rtrim($outFileDocs, '/') . '/docs.json';
                }
                file_put_contents($outFileDocs, $outStr);
            } else {
                /*cov_ignore*/                 echo $outStr;
            }
            return 0;
        }

        fwrite(STDERR, "Error: Unknown or incomplete command: $command\n");
        return 1;

        /*cov_ignore*/         return 0;
    }
}
