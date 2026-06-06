<?php

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Emits PHP CLI code, fully typed and documented.
 * @param array $paths
 * @param string $existingCode
 * @return string
 */
function emit(array $paths, string $existingCode = ''): string
{
    $out = "<?php\n\n/**\n * Auto-generated API CLI\n * Usage: php api_cli.php <command> [args]\n */\n\n";
    $out .= "require_once __DIR__ . '/ApiClient.php';\n\n";
    $out .= "\$client = new ApiClient('http://localhost');\n\n";
    $out .= "\$command = \$argv[1] ?? '--help';\n\n";
    $out .= "if (\$command === '--help' || \$command === '-h') {\n";
    $out .= "    echo \"Usage: php api_cli.php <command> [args]\\n\\n\";\n";
    $out .= "    echo \"Commands:\\n\";\n";
    $commands = [];
    $to_snake_case = function (string $text): string {
        $preg_replace = 'preg_replace';
        $strtolower = 'strtolower';
        return $strtolower($preg_replace('/(?<!^)[A-Z]/', '_$0', $text));
    };

    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $operation) {
            $in_array = 'in_array';
            $strtolower = 'strtolower';
            if ($in_array($strtolower($method), ['parameters', 'summary', 'description', 'servers'])) {
                continue;
            }
            $preg_replace = 'preg_replace';
            $opId = $operation['operationId'] ?? strtolower($method) . $preg_replace('/[^a-zA-Z0-9]/', '', $path);
            $opId = $to_snake_case($opId);
            $commands[$opId] = $operation;
            $desc = $operation['description'] ?? 'Call ' . strtoupper($method) . ' ' . $path;
            $out .= "    echo \"  " . str_pad($opId, 25) . " $desc\\n\";\n";
        }
    }
    $out .= "    exit(0);\n";
    $out .= "}\n\n";

    foreach ($commands as $opId => $operation) {
        $out .= "if (\$command === '$opId') {\n";
        $out .= "    if (isset(\$argv[2]) && (\$argv[2] === '--help' || \$argv[2] === '-h')) {\n";
        $out .= "        echo \"Usage: php api_cli.php $opId [args]\\n\\n\";\n";
        if (isset($operation['description'])) {
            $out .= "        echo \"" . addslashes($operation['description']) . "\\n\\n\";\n";
        }
        $out .= "        echo \"Options:\\n\";\n";

        $paramsHelp = [];
        if (isset($operation['parameters'])) {
            foreach ($operation['parameters'] as $p) {
                $name = $p['name'] ?? 'param';
                $req = !empty($p['required']) ? '(required)' : '(optional)';
                $desc = $p['description'] ?? '';
                $out .= "        echo \"  --$name $req $desc\\n\";\n";
            }
        }
        if (isset($operation['requestBody'])) {
            $out .= "        echo \"  --body (optional) JSON body\\n\";\n";
        }
        $out .= "        exit(0);\n";
        $out .= "    }\n";

        $out .= "    \$params = [];\n";
        $out .= "    \$body = [];\n";
        if (isset($operation['parameters'])) {
            foreach ($operation['parameters'] as $p) {
                $name = $p['name'] ?? 'param';
                $out .= "    \$params['$name'] = null;\n";
            }
        }
        $out .= "    for (\$i = 2; \$i < \$argc; \$i++) {\n";
        $out .= "        if (strpos(\$argv[\$i], '--') === 0 && isset(\$argv[\$i+1])) {\n";
        $out .= "            \$key = substr(\$argv[\$i], 2);\n";
        $out .= "            if (\$key === 'body') {\n";
        $out .= "                \$body = json_decode(\$argv[++\$i], true);\n";
        $out .= "            } else {\n";
        $out .= "                \$params[\$key] = \$argv[++\$i];\n";
        $out .= "            }\n";
        $out .= "        }\n";
        $out .= "    }\n";
        $out .= "    \$response = \$client->$opId(\$params, \$body);\n";
        $out .= "    echo json_encode(\$response, JSON_PRETTY_PRINT) . \"\\n\";\n";
        $out .= "    exit(0);\n";
        $out .= "}\n\n";
    }

    $out .= "if (\$command === 'mcp') {\n";
    $out .= "    \$capabilities = ['tools' => ['listChanged' => true]];\n";
    $out .= "    while ((\$line = fgets(STDIN)) !== false) {\n";
    $out .= "        \$req = json_decode(\$line, true);\n";
    $out .= "        if (!\$req) continue;\n";
    $out .= "        \$isNotification = !array_key_exists('id', \$req);\n";
    $out .= "        \$res = ['jsonrpc' => '2.0', 'id' => \$req['id'] ?? null];\n";
    $out .= "        if (isset(\$req['method'])) {\n";
    $out .= "            if (\$req['method'] === 'initialize') {\n";
    $out .= "                \$res['result'] = [\n";
    $out .= "                    'protocolVersion' => '2024-11-05',\n";
    $out .= "                    'capabilities' => \$capabilities,\n";
    $out .= "                    'serverInfo' => ['name' => 'generated-api-mcp', 'version' => '1.0.0']\n";
    $out .= "                ];\n";
    $out .= "            } elseif (\$req['method'] === 'initialized') {\n";
    $out .= "                continue;\n";
    $out .= "            } elseif (\$req['method'] === 'ping') {\n";
    $out .= "                \$res['result'] = [];\n";
    $out .= "            } elseif (\$req['method'] === 'prompts/list') {\n";
    $out .= "                \$res['result'] = ['prompts' => []];\n";
    $out .= "            } elseif (\$req['method'] === 'prompts/get') {\n";
    $out .= "                \$res['error'] = ['code' => -32602, 'message' => 'Prompt not found'];\n";
    $out .= "            } elseif (\$req['method'] === 'logging/setLevel') {\n";
    $out .= "                \$res['result'] = [];\n";
    $out .= "            } elseif (\$req['method'] === 'resources/templates/list') {\n";
    $out .= "                \$res['result'] = ['resourceTemplates' => []];\n";
    $out .= "            } elseif (\$req['method'] === 'completion/complete') {\n";
    $out .= "                \$res['result'] = ['completion' => ['values' => [], 'hasMore' => false]];\n";
    $out .= "            } elseif (\$req['method'] === 'notifications/cancelled') {\n";
    $out .= "                continue;\n";
    $out .= "            } elseif (\$req['method'] === 'tools/list') {\n";
    $out .= "                \$tools = [];\n";
    foreach ($commands as $opId => $operation) {
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
        $out .= "                \$tools[] = [\n";
        $out .= "                    'name' => '$opId',\n";
        $out .= "                    'description' => '" . addslashes($desc) . "',\n";
        $out .= "                    'inputSchema' => json_decode('$schemaJson', true)\n";
        $out .= "                ];\n";
    }
    $out .= "                \$res['result'] = ['tools' => \$tools];\n";

    // Add resources/list
    $out .= "            } elseif (\$req['method'] === 'resources/list') {\n";
    $out .= "                \$res['result'] = ['resources' => [\n";
    $out .= "                    [\n";
    $out .= "                        'uri' => 'api://docs',\n";
    $out .= "                        'name' => 'API Documentation',\n";
    $out .= "                        'mimeType' => 'text/plain'\n";
    $out .= "                    ]\n";
    $out .= "                ]];\n";

    // Add resources/read
    $out .= "            } elseif (\$req['method'] === 'resources/read') {\n";
    $out .= "                \$uri = \$req['params']['uri'] ?? '';\n";
    $out .= "                if (\$uri === 'api://docs') {\n";
    $out .= "                    \$res['result'] = ['contents' => [\n";
    $out .= "                        [\n";
    $out .= "                            'uri' => 'api://docs',\n";
    $out .= "                            'mimeType' => 'text/plain',\n";
    $docs = [];
    foreach ($paths as $path => $methods) {
        foreach ($methods as $m => $op) {
            if (in_array(strtolower($m), ['parameters', 'summary', 'description', 'servers', 'additionaloperations'])) {
                continue;
            }
            $docs[] = strtoupper($m) . " " . $path;
        }
    }
    $out .= "                            'text' => \"API Endpoints:\\n" . implode("\\n", $docs) . "\"\n";
    $out .= "                        ]\n";
    $out .= "                    ]];\n";
    $out .= "                } else {\n";
    $out .= "                    \$res['error'] = ['code' => -32602, 'message' => 'Invalid URI'];\n";
    $out .= "                }\n";

    $out .= "            } elseif (\$req['method'] === 'tools/call') {\n";
    $out .= "                \$name = \$req['params']['name'] ?? '';\n";
    $out .= "                \$args = \$req['params']['arguments'] ?? [];\n";
    $out .= "                try {\n";
    $out .= "                    \$callRes = null;\n";
    $out .= "                    switch (\$name) {\n";
    foreach ($commands as $opId => $operation) {
        $out .= "                        case '$opId':\n";
        $out .= "                            \$params = [];\n";
        $out .= "                            \$body = [];\n";
        if (isset($operation['parameters'])) {
            foreach ($operation['parameters'] as $p) {
                $pName = $p['name'] ?? 'param';
                $out .= "                            \$params['$pName'] = \$args['$pName'] ?? null;\n";
            }
        }
        if (isset($operation['requestBody'])) {
            $out .= "                            \$body = \$args['body'] ?? [];\n";
        }
        $out .= "                            \$callRes = \$client->$opId(\$params, \$body);\n";
        $out .= "                            break;\n";
    }
    $out .= "                        default:\n";
    $out .= "                            throw new \Exception(\"Unknown tool: \" . \$name);\n";
    $out .= "                    }\n";
    $out .= "                    \$res['result'] = [\n";
    $out .= "                        'content' => [['type' => 'text', 'text' => is_string(\$callRes) ? \$callRes : json_encode(\$callRes)]]\n";
    $out .= "                    ];\n";
    $out .= "                } catch (\Throwable \$e) {\n";
    $out .= "                    \$res['result'] = [\n";
    $out .= "                        'content' => [['type' => 'text', 'text' => 'Error: ' . \$e->getMessage()]],\n";
    $out .= "                        'isError' => true\n";
    $out .= "                    ];\n";
    $out .= "                }\n";
    $out .= "            } else {\n";
    $out .= "                \$res['error'] = ['code' => -32601, 'message' => 'Method not found'];\n";
    $out .= "            }\n";
    $out .= "        }\n";
    $out .= "        if (!\$isNotification) {\n";
    $out .= "            echo json_encode(\$res) . \"\\n\";\n";
    $out .= "        }\n";
    $out .= "    }\n";
    $out .= "    exit(0);\n";
    $out .= "}\n\n";

    $out .= "echo \"Unknown command: \$command\\n\";\nexit(1);\n";

    return $out;
}
