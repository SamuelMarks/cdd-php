<?php

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Emits PHP CLI code, fully typed and documented.
 * @param array $paths
 * @param string $existingCode
 * @return string
 */
function emit(array $paths, string $existingCode = ''): string {
    $out = "<?php\n\n/**\n * Auto-generated API CLI\n * Usage: php api_cli.php <command> [args]\n */\n\n";
    $out .= "require_once __DIR__ . '/ApiClient.php';\n\n";
    $out .= "\$client = new ApiClient('http://localhost');\n\n";
    $out .= "\$command = \$argv[1] ?? '--help';\n\n";
    $out .= "if (\$command === '--help' || \$command === '-h') {\n";
    $out .= "    echo \"Usage: php api_cli.php <command> [args]\\n\\n\";\n";
    $out .= "    echo \"Commands:\\n\";\n";
    $commands = [];
    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if (in_array(strtolower($method), ['parameters', 'summary', 'description', 'servers'])) continue;
            $opId = $operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path);
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
                $out .= "        echo \"  --$name \$req $desc\\n\";\n";
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
    
    $out .= "echo \"Unknown command: \$command\\n\";\nexit(1);\n";
    
    return $out;
}
