<?php

declare(strict_types=1);

namespace Cdd\Cli;

class CddCli
{
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

    public static function run(array $argv): int
    {
        $argc = count($argv);
        $baseDir = dirname(__DIR__);

        if (!defined('STDIN')) {
            define('STDIN', @fopen('php://stdin', 'r'));
        }
        if (!defined('STDOUT')) {
            define('STDOUT', @fopen('php://stdout', 'w'));
        }
        if (!defined('STDERR')) {
            define('STDERR', @fopen('php://stderr', 'w'));
        }

        if (file_exists("$baseDir/vendor/autoload.php")) {
            require_once "$baseDir/vendor/autoload.php";
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
            echo "0.0.1\n";
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
            echo "  cdd-php from_openapi to_sdk_cli -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests]\n";
            echo "  cdd-php from_openapi to_sdk -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests]\n";
            echo "  cdd-php from_openapi to_server -i spec.json -o target_directory\n";
            echo "  cdd-php sync -d directory\n";
            return 0;
        }

        if ($command === "mcp") {
            $capabilities = ['tools' => ['listChanged' => true], 'resources' => ['listChanged' => true]];
            while (($line = fgets(STDIN)) !== false) {
                $req = json_decode($line, true);
                if (!$req) {
                    continue;
                }
                $res = ['jsonrpc' => '2.0', 'id' => $req['id'] ?? null];
                if (isset($req['method'])) {
                    if ($req['method'] === 'initialize') {
                        $res['result'] = [
                            'protocolVersion' => '2024-11-05',
                            'capabilities' => $capabilities,
                            'serverInfo' => ['name' => 'cdd-php-mcp', 'version' => '0.0.1']
                        ];
                    } elseif ($req['method'] === 'initialized') {
                        continue;
                    } elseif ($req['method'] === 'ping') {
                        $res['result'] = [];
                    } elseif ($req['method'] === 'resources/list') {
                        $res['result'] = ['resources' => [
                            ['uri' => 'cdd://ast', 'name' => 'Internal AST Query Resource', 'mimeType' => 'text/plain'],
                            ['uri' => 'cdd://schema', 'name' => 'Schema Inspection Resource', 'mimeType' => 'application/json']
                        ]];
                    } elseif ($req['method'] === 'resources/read') {
                        $uri = $req['params']['uri'] ?? '';
                        if ($uri === 'cdd://ast' || $uri === 'cdd://schema') {
                            $res['result'] = ['contents' => [['uri' => $uri, 'mimeType' => 'text/plain', 'text' => '{}']]];
                        } else {
                            $res['error'] = ['code' => -32602, 'message' => 'Invalid URI'];
                        }
                    } elseif ($req['method'] === 'tools/list') {
                        $res['result'] = ['tools' => [
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
                        ]];
                    } elseif ($req['method'] === 'tools/call') {
                        $name = $req['params']['name'] ?? '';
                        $args = $req['params']['arguments'] ?? [];
                        try {
                            ob_start();
                            if ($name === 'from_openapi') {
                                self::run(['cdd-php', 'from_openapi', '-i', $args['input'], '-o', $args['output']]);
                            } elseif ($name === 'to_openapi') {
                                self::run(['cdd-php', 'to_openapi', '-i', $args['input'], '-o', $args['output']]);
                            } elseif ($name === 'sync') {
                                self::run(['cdd-php', 'sync', '-d', $args['dir']]);
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
                echo json_encode($res) . "\n";
            }
            return 0;
        }

        if ($command === "serve_json_rpc") {
            $port = (int)($_ENV["CDD_PORT"] ?? 8080);
            $listen = $_ENV["CDD_LISTEN"] ?? "127.0.0.1";
            for ($i = 2; $i < $argc; $i++) {
                if (($argv[$i] === "--port" || $argv[$i] === "-p") && isset($argv[$i + 1])) {
                    $port = (int)$argv[++$i];
                } elseif (($argv[$i] === "--listen" || $argv[$i] === "-l") && isset($argv[$i + 1])) {
                    $listen = $argv[++$i];
                }
            }
            $serverUrl = "tcp://$listen:$port";
            $socket = stream_socket_server($serverUrl, $errno, $errstr);
            if (!$socket) {
                die("Error starting server: $errstr ($errno)
");
            }
            echo "JSON-RPC server listening on $serverUrl
";
            while ($conn = @stream_socket_accept($socket)) {
                $request = "";
                while ($data = fread($conn, 8192)) {
                    $request .= $data;
                    if (strpos($request, "

") !== false) {
                        break;
                    }
                }
                $headersEnd = strpos($request, "

");
                $body = "";
                if ($headersEnd !== false) {
                    if (preg_match("/Content-Length:\s*(\d+)/i", $request, $m)) {
                        $len = (int)$m[1];
                        $body = substr($request, $headersEnd + 4);
                        while (strlen($body) < $len) {
                            $body .= fread($conn, 8192);
                        }
                    }
                }

                $reqData = json_decode($body, true);
                $res = ["jsonrpc" => "2.0", "id" => $reqData["id"] ?? null];

                if (!$reqData || !isset($reqData["method"])) {
                    $res["error"] = ["code" => -32600, "message" => "Invalid Request"];
                } else {
                    $m = $reqData["method"];
                    $p = $reqData["params"] ?? [];
                    $cmdArgs = [];
                    if (is_array($p)) {
                        foreach ($p as $k => $v) {
                            if (is_int($k)) {
                                $cmdArgs[] = escapeshellarg((string)$v);
                            } else {
                                $cmdArgs[] = "--" . escapeshellarg((string)$k) . " " . escapeshellarg((string)$v);
                            }
                        }
                    }
                    $binPath = class_exists('\Phar') && \Phar::running(false) ? \Phar::running(false) : __FILE__;
                    $cmd = "php " . escapeshellarg($binPath) . " " . escapeshellarg($m) . " " . implode(" ", $cmdArgs);
                    $res["result"] = shell_exec($cmd);
                }
                $resBody = json_encode($res);
                $response = "HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: " . strlen($resBody) . "

" . $resBody;
                fwrite($conn, $response);
                fclose($conn);
            }
            return 0;
        }

        if ($command === 'test') {
            require_once dirname($baseDir) . "/tests/framework/Runner.php";
            $testDir = dirname($baseDir) . "/tests";
            if (isset($argv[2])) {
                $testDir = $resolvePath($argv[2]);
            }
            \Cdd\Tests\Framework\Runner::run($testDir);
            return 0;
        }

        if ($command === 'sync') {
            $dir = '';
            for ($i = 2; $i < $argc; $i++) {
                if ($argv[$i] === '-d' && isset($argv[$i + 1])) {
                    $dir = $resolvePath($argv[$i + 1]);
                    $i++;
                } elseif ($argv[$i] !== '-d' && $dir === '') {
                    $dir = $resolvePath($argv[$i]);
                }
            }

            if (!$dir || !is_dir($dir)) {
                echo "Error: Directory not found.\n";
                return 1;
            }

            // Core syncing logic: read the whole out directory, parse components, merge, and re-emit.
            $openapi = [
                'openapi' => '3.2.0',
                'info' => ['title' => 'Synced API', 'version' => '0.0.1'],
                'paths' => [],
                'components' => ['schemas' => []]
            ];

            // If api_metadata.php exists, merge it
            if (file_exists("$dir/src/api_metadata.php")) {
                $metadata = include "$dir/src/api_metadata.php";
                if (is_array($metadata)) {
                    foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
                        if (isset($metadata[$key])) {
                            $openapi[$key] = $metadata[$key];
                        }
                    }
                }
            }

            // If routes.php exists, parse it
            if (file_exists("$dir/src/routes.php")) {
                $code = file_get_contents("$dir/src/routes.php");
                $routes = function_exists('\Cdd\Routes\parse') ? \Cdd\Routes\parse($code) : [];
                if (!empty($routes)) {
                    $openapi['paths'] = array_replace_recursive((array)$openapi['paths'], $routes);
                }
            }

            // If ApiClient.php exists, parse it and merge
            if (file_exists("$dir/src/ApiClient.php")) {
                $clientPaths = \Cdd\Client\parse(file_get_contents("$dir/src/ApiClient.php"));
                if (!empty($clientPaths)) {
                    $openapi['paths'] = array_replace_recursive((array)$openapi['paths'], $clientPaths);
                }
            }

            // If ApiController.php exists, parse it and merge into paths
            if (file_exists("$dir/src/ApiController.php") && function_exists('\Cdd\Controllers\parse')) {
                $controllerOps = \Cdd\Controllers\parse(file_get_contents("$dir/src/ApiController.php"));
                if (!empty($controllerOps) && isset($openapi['paths'])) {
                    foreach ($openapi['paths'] as $path => &$methods) {
                        foreach ($methods as $method => &$operation) {
                            $opId = $operation['operationId'] ?? '';
                            if (isset($controllerOps[$opId])) {
                                $operation = array_replace_recursive((array)$operation, $controllerOps[$opId]);
                            }
                        }
                    }
                }
            }

            // If api_cli.php exists, parse it and merge into paths
            if (file_exists("$dir/src/api_cli.php") && function_exists('\Cdd\Cli\parse')) {
                $cliOps = \Cdd\Cli\parse(file_get_contents("$dir/src/api_cli.php"));
                if (!empty($cliOps) && isset($openapi['paths'])) {
                    foreach ($openapi['paths'] as $path => &$methods) {
                        foreach ($methods as $method => &$operation) {
                            $opId = $operation['operationId'] ?? '';
                            if (isset($cliOps["/cli/".$opId])) {
                                $operation = array_replace_recursive((array)$operation, $cliOps["/cli/".$opId]);
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
                            $openapi['components'][$type] = [];
                        }
                        if ($type === 'mediaTypes') {
                            $mediaType = ['schema' => $schema];

                            // Parse extra docblock tags for Media Type Objects (3.2.0)
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if (isset($parsedDoc['tags']['itemSchema'])) {
                                    $mediaType['itemSchema'] = ['$ref' => '#/components/schemas/' . trim($parsedDoc['tags']['itemSchema'][0])];
                                }
                                // Simplified encoding representation
                                if (isset($parsedDoc['tags']['itemEncoding'])) {
                                    $mediaType['itemEncoding'] = ['contentType' => trim($parsedDoc['tags']['itemEncoding'][0])];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = $mediaType;
                        } elseif ($type === 'parameters') {
                            $paramName = $c['name'];
                            $in = 'query';
                            $required = false;
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if (isset($parsedDoc['tags']['in'])) {
                                    $in = trim($parsedDoc['tags']['in'][0]);
                                }
                                if (isset($parsedDoc['tags']['name'])) {
                                    $paramName = trim($parsedDoc['tags']['name'][0]);
                                }
                                if (isset($parsedDoc['tags']['required'])) {
                                    $required = true;
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'name' => $paramName,
                                'in' => $in,
                                'required' => $required,
                                'schema' => $schema
                            ];
                        } elseif ($type === 'responses') {
                            $desc = $c['name'];
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc,
                                'content' => ['application/json' => ['schema' => $schema]]
                            ];
                        } elseif ($type === 'requestBodies') {
                            $desc = '';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc,
                                'content' => ['application/json' => ['schema' => $schema]]
                            ];
                        } elseif ($type === 'headers') {
                            $desc = '';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc,
                                'schema' => $schema
                            ];
                        } elseif ($type === 'securitySchemes') {
                            $schemeType = 'http';
                            $scheme = 'bearer';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if (isset($parsedDoc['tags']['type'])) {
                                    $schemeType = trim($parsedDoc['tags']['type'][0]);
                                }
                                if (isset($parsedDoc['tags']['scheme'])) {
                                    $scheme = trim($parsedDoc['tags']['scheme'][0]);
                                }
                            }
                            $secScheme = ['type' => $schemeType];
                            if ($schemeType === 'http') {
                                $secScheme['scheme'] = $scheme;
                            } elseif ($schemeType === 'apiKey') {
                                $secScheme['in'] = 'header';
                                $secScheme['name'] = 'X-API-Key';
                                if (isset($parsedDoc['tags']['in'])) {
                                    $secScheme['in'] = trim($parsedDoc['tags']['in'][0]);
                                }
                                if (isset($parsedDoc['tags']['name'])) {
                                    $secScheme['name'] = trim($parsedDoc['tags']['name'][0]);
                                }
                            } elseif ($schemeType === 'oauth2') {
                                $secScheme['flows'] = [];
                                if (isset($parsedDoc['tags']['flow'])) {
                                    foreach ($parsedDoc['tags']['flow'] as $flowStr) {
                                        $parts = explode(' ', $flowStr, 2);
                                        if (isset($parts[1])) {
                                            $secScheme['flows'][$parts[0]] = json_decode($parts[1], true);
                                        }
                                    }
                                }
                            } elseif ($schemeType === 'openIdConnect') {
                                if (isset($parsedDoc['tags']['openIdConnectUrl'])) {
                                    $secScheme['openIdConnectUrl'] = trim($parsedDoc['tags']['openIdConnectUrl'][0]);
                                }
                            }
                            if (isset($parsedDoc['tags']['bearerFormat'])) {
                                $secScheme['bearerFormat'] = trim($parsedDoc['tags']['bearerFormat'][0]);
                            }
                            $openapi['components'][$type][$c['name']] = $secScheme;
                        } elseif ($type === 'pathItems') {
                            $desc = '';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc
                            ];
                        } elseif ($type === 'callbacks') {
                            $openapi['components'][$type][$c['name']] = [
                                '{$request.query.callbackUrl}' => [
                                    'post' => [
                                        'requestBody' => [
                                            'content' => ['application/json' => ['schema' => $schema]]
                                        ],
                                        'responses' => [
                                            '200' => ['description' => 'ok']
                                        ]
                                    ]
                                ]
                            ];
                        } elseif ($type === 'links') {
                            $desc = '';
                            $opId = 'linkOperation';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                                if (isset($parsedDoc['tags']['operationId'])) {
                                    $opId = trim($parsedDoc['tags']['operationId'][0]);
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'operationId' => $opId,
                                'description' => $desc
                            ];
                        } else {
                            $openapi['components'][$type][$c['name']] = $schema;
                        }
                    }
                }
            }

            // If mocks.php exists, parse it
            if (file_exists("$dir/src/mocks.php")) {
                $mocks = \Cdd\Mocks\parse(file_get_contents("$dir/src/mocks.php"));
                if (!empty($mocks)) {
                    $openapi['components']['examples'] = $mocks;
                    // update schema to match mocks (if I edit a mock, update the rest to match)
                    foreach ($mocks as $name => $example) {
                        if (isset($example['dataValue']) && is_array($example['dataValue'])) {
                            $schemaName = ucfirst($name) . 'Model';
                            if (!isset($openapi['components']['schemas'][$schemaName])) {
                                $properties = [];
                                foreach ($example['dataValue'] as $key => $val) {
                                    $type = gettype($val);
                                    if ($type === 'integer') {
                                        $properties[$key] = ['type' => 'integer'];
                                    } elseif ($type === 'double') {
                                        $properties[$key] = ['type' => 'number'];
                                    } elseif ($type === 'boolean') {
                                        $properties[$key] = ['type' => 'boolean'];
                                    } elseif ($type === 'array') {
                                        $properties[$key] = ['type' => 'array', 'items' => ['type' => 'string']];
                                    } else {
                                        $properties[$key] = ['type' => 'string'];
                                    }
                                }
                                $openapi['components']['schemas'][$schemaName] = [
                                    'type' => 'object',
                                    'properties' => $properties
                                ];
                            }
                        }
                    }
                }
            }

            // If ApiServers.php exists, parse it
            if (file_exists("$dir/src/ApiServers.php")) {
                $parsedServers = \Cdd\Servers\parse(file_get_contents("$dir/src/ApiServers.php"));
                if (!empty($parsedServers)) {
                    $openapi['servers'] = $parsedServers;
                }
            }

            // If Webhooks.php exists, parse it
            if (file_exists("$dir/Webhooks.php") && function_exists('\Cdd\Webhooks\parse')) {
                $webhooks = \Cdd\Webhooks\parse(file_get_contents("$dir/Webhooks.php"));
                if (!empty($webhooks)) {
                    $openapi['webhooks'] = $webhooks;
                }
            }

            // If ApiTests.php or ComposableTests.php exists, parse it
            if (file_exists("$dir/src/ComposableTests.php")) {
                $tests = \Cdd\Tests\parse(file_get_contents("$dir/src/ComposableTests.php"));
            } elseif (file_exists("$dir/src/ApiTests.php")) {
                $tests = \Cdd\Tests\parse(file_get_contents("$dir/src/ApiTests.php"));
            }

            // Emitting back to sync the project and OpenAPI json
            $options = [];
            if (file_exists("$dir/src/ComposableTests.php") || file_exists("$dir/src/ApiTests.php") || file_exists("$dir/src/mocks.php")) {
                $options['tests'] = true;
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
                } elseif ($argv[$i] !== '-i' && $argv[$i] !== '--input' && $argv[$i] !== '-o' && $argv[$i] !== '--output' && $file === '') {
                    $file = $resolvePath($argv[$i]);
                }
            }

            if (!$file || !file_exists($file)) {
                echo "Error: File not found.\n";
                return 1;
            }
            $code = file_get_contents($file);

            $openapi = [
                'openapi' => '3.2.0',
                'info' => [
                    'title' => 'Parsed API',
                    'version' => '0.0.1',
                ],
                'paths' => [],
                'components' => ['schemas' => []]
            ];

            $dir = dirname($file);
            if (file_exists("$dir/src/api_metadata.php")) {
                $metadata = include "$dir/src/api_metadata.php";
                if (is_array($metadata)) {
                    foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
                        if (isset($metadata[$key])) {
                            $openapi[$key] = $metadata[$key];
                        }
                    }
                }
            }

            if (strpos($code, 'curl_exec') !== false) {
                $openapi['paths'] = \Cdd\Client\parse($code);
            } else {

                $routes = function_exists('\Cdd\Routes\parse') ? \Cdd\Routes\parse($code) : [];
                if (!empty($routes)) {
                    $openapi['paths'] = $routes;
                }

                if (function_exists('\Cdd\Controllers\parse') && !empty($openapi['paths'])) {
                    $controllerOps = \Cdd\Controllers\parse($code);
                    foreach ($openapi['paths'] as $path => &$methods) {
                        foreach ($methods as $method => &$operation) {
                            $opId = $operation['operationId'] ?? '';
                            if (isset($controllerOps[$opId])) {
                                $operation = array_replace_recursive((array)$operation, $controllerOps[$opId]);
                            }
                        }
                    }
                }

                if (function_exists('\Cdd\Cli\parse') && !empty($openapi['paths'])) {
                    $cliOps = \Cdd\Cli\parse($code);
                    foreach ($openapi['paths'] as $path => &$methods) {
                        foreach ($methods as $method => &$operation) {
                            $opId = $operation['operationId'] ?? '';
                            if (isset($cliOps["/cli/".$opId])) {
                                $operation = array_replace_recursive((array)$operation, $cliOps["/cli/".$opId]);
                            }
                        }
                    }
                }

                if (function_exists('\Cdd\Servers\parse')) {
                    $servers = \Cdd\Servers\parse($code);
                    if (!empty($servers)) {
                        $openapi['servers'] = $servers;
                    }
                }

                $classes = function_exists('\Cdd\Classes\parse') ? \Cdd\Classes\parse($code) : [];
                if (!empty($classes)) {
                    foreach ($classes as $c) {
                        $schema = \Cdd\Schemas\parse($c['node']);
                        $type = $c['componentType'] ?? 'schemas';
                        if (!isset($openapi['components'][$type])) {
                            $openapi['components'][$type] = [];
                        }
                        if ($type === 'mediaTypes') {
                            $mediaType = ['schema' => $schema];

                            // Parse extra docblock tags for Media Type Objects (3.2.0)
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if (isset($parsedDoc['tags']['itemSchema'])) {
                                    $mediaType['itemSchema'] = ['$ref' => '#/components/schemas/' . trim($parsedDoc['tags']['itemSchema'][0])];
                                }
                                // Simplified encoding representation
                                if (isset($parsedDoc['tags']['itemEncoding'])) {
                                    $mediaType['itemEncoding'] = ['contentType' => trim($parsedDoc['tags']['itemEncoding'][0])];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = $mediaType;
                        } elseif ($type === 'parameters') {
                            $paramName = $c['name'];
                            $in = 'query';
                            $required = false;
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if (isset($parsedDoc['tags']['in'])) {
                                    $in = trim($parsedDoc['tags']['in'][0]);
                                }
                                if (isset($parsedDoc['tags']['name'])) {
                                    $paramName = trim($parsedDoc['tags']['name'][0]);
                                }
                                if (isset($parsedDoc['tags']['required'])) {
                                    $required = true;
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'name' => $paramName,
                                'in' => $in,
                                'required' => $required,
                                'schema' => $schema
                            ];
                        } elseif ($type === 'responses') {
                            $desc = $c['name'];
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc,
                                'content' => ['application/json' => ['schema' => $schema]]
                            ];
                        } elseif ($type === 'requestBodies') {
                            $desc = '';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc,
                                'content' => ['application/json' => ['schema' => $schema]]
                            ];
                        } elseif ($type === 'headers') {
                            $desc = '';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc,
                                'schema' => $schema
                            ];
                        } elseif ($type === 'securitySchemes') {
                            $schemeType = 'http';
                            $scheme = 'bearer';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if (isset($parsedDoc['tags']['type'])) {
                                    $schemeType = trim($parsedDoc['tags']['type'][0]);
                                }
                                if (isset($parsedDoc['tags']['scheme'])) {
                                    $scheme = trim($parsedDoc['tags']['scheme'][0]);
                                }
                            }
                            $secScheme = ['type' => $schemeType];
                            if ($schemeType === 'http') {
                                $secScheme['scheme'] = $scheme;
                            } elseif ($schemeType === 'apiKey') {
                                $secScheme['in'] = 'header';
                                $secScheme['name'] = 'X-API-Key';
                                if (isset($parsedDoc['tags']['in'])) {
                                    $secScheme['in'] = trim($parsedDoc['tags']['in'][0]);
                                }
                                if (isset($parsedDoc['tags']['name'])) {
                                    $secScheme['name'] = trim($parsedDoc['tags']['name'][0]);
                                }
                            } elseif ($schemeType === 'oauth2') {
                                $secScheme['flows'] = [];
                                if (isset($parsedDoc['tags']['flow'])) {
                                    foreach ($parsedDoc['tags']['flow'] as $flowStr) {
                                        $parts = explode(' ', $flowStr, 2);
                                        if (isset($parts[1])) {
                                            $secScheme['flows'][$parts[0]] = json_decode($parts[1], true);
                                        }
                                    }
                                }
                            } elseif ($schemeType === 'openIdConnect') {
                                if (isset($parsedDoc['tags']['openIdConnectUrl'])) {
                                    $secScheme['openIdConnectUrl'] = trim($parsedDoc['tags']['openIdConnectUrl'][0]);
                                }
                            }
                            if (isset($parsedDoc['tags']['bearerFormat'])) {
                                $secScheme['bearerFormat'] = trim($parsedDoc['tags']['bearerFormat'][0]);
                            }
                            $openapi['components'][$type][$c['name']] = $secScheme;
                        } elseif ($type === 'pathItems') {
                            $desc = '';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'description' => $desc
                            ];
                        } elseif ($type === 'callbacks') {
                            $openapi['components'][$type][$c['name']] = [
                                '{$request.query.callbackUrl}' => [
                                    'post' => [
                                        'requestBody' => [
                                            'content' => ['application/json' => ['schema' => $schema]]
                                        ],
                                        'responses' => [
                                            '200' => ['description' => 'ok']
                                        ]
                                    ]
                                ]
                            ];
                        } elseif ($type === 'links') {
                            $desc = '';
                            $opId = 'linkOperation';
                            $docComment = $c['node']->getDocComment();
                            if ($docComment !== null) {
                                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                                if ($parsedDoc['description'] !== '') {
                                    $desc = explode("\n", $parsedDoc['description'])[0];
                                }
                                if (isset($parsedDoc['tags']['operationId'])) {
                                    $opId = trim($parsedDoc['tags']['operationId'][0]);
                                }
                            }
                            $openapi['components'][$type][$c['name']] = [
                                'operationId' => $opId,
                                'description' => $desc
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
                unset($openapi['components']);
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

            $newArgv = [];
            for ($k = 0; $k < $argc; $k++) {
                if ($argv[$k] === '--no-github-actions') {
                    $noGithubActions = true;
                } elseif ($argv[$k] === '--no-installable-package') {
                    $noInstallablePackage = true;
                } elseif ($argv[$k] === '--tests') {
                    $tests = true;
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
            } elseif ($inputDir !== '') {
                if (!is_dir($inputDir)) {
                    echo "Error: Input directory not found.\n";
                    return 1;
                }
                // For now, emit a simple combination or handle first file
                $files = glob("$inputDir/*.json");
                if (empty($files)) {
                    echo "Error: No .json files found in input dir.\n";
                    return 1;
                }
                $spec = \Cdd\Openapi\parse(file_get_contents($files[0]));
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
            }

            echo "Emitted code to $dir successfully.
";

            if (!$noInstallablePackage) {
                if (!file_exists("$dir/composer.json")) {
                    file_put_contents("$dir/composer.json", json_encode([
                        "name" => "offscale/generated-api",
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
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }

            if (!$noGithubActions) {
                if (!is_dir("$dir/.github/workflows")) {
                    mkdir("$dir/.github/workflows", 0777, true);
                }
                if (!file_exists("$dir/.github/workflows/ci.yml")) {
                    file_put_contents("$dir/.github/workflows/ci.yml", "name: CI
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
                fwrite(STDERR, "Error parsing JSON spec.\n");
                return 1;
            }

            $operations = [];
            if (isset($spec['paths'])) {
                foreach ($spec['paths'] as $path => $methods) {
                    foreach ($methods as $method => $operation) {
                        if (in_array(strtolower($method), ['parameters', 'summary', 'description', 'servers'])) {
                            continue;
                        }

                        $opId = $operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path);

                        // Generate basic snippet
                        $camelOpId = preg_replace_callback('/[-_](.)/', function ($m) {
                            return strtoupper($m[1]);
                        }, $opId);

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

                        $snippet = "\$response = \$client->{$camelOpId}($paramStr);\nprint_r(\$response);";

                        $codeBlock = [
                            'snippet' => $snippet
                        ];

                        if (!$noImports) {
                            $codeBlock['imports'] = "require_once 'vendor/autoload.php';\nuse ApiClient;";
                        }

                        if (!$noWrapping) {
                            $codeBlock['wrapper_start'] = "\$client = new ApiClient('https://api.example.com');";
                            $codeBlock['wrapper_end'] = "";
                        }

                        $operations[] = [
                            'method' => strtoupper($method),
                            'path' => $path,
                            'operationId' => $opId,
                            'code' => $codeBlock
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
                    $outFileDocs = rtrim($outFileDocs, '/') . '/docs.json';
                }
                file_put_contents($outFileDocs, $outStr);
            } else {
                echo $outStr;
            }
            return 0;
        }

        fwrite(STDERR, "Error: Unknown or incomplete command: $command\n");
        return 1;

        return 0;
    }
}
