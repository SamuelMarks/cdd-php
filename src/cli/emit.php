<?php

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Emits PHP CLI code.
 * @param array $paths
 * @param string $existingCode
 * @return string
 */
function emit(array $paths, string $existingCode = ''): string {
    $out = "<?php

/**
 * Auto-generated API CLI
 * Usage: php api_cli.php <command> [args]
 */

";
    $out .= "require_once __DIR__ . '/ApiClient.php';

";
    $out .= "\$client = new ApiClient('http://localhost');

";
    $out .= "\$command = \$argv[1] ?? '--help';

";
    $out .= "if (\$command === '--help' || \$command === '-h') {
";
    $out .= "    echo \"Usage: php api_cli.php <command> [args]\\n\";
";
    $out .= "    echo \"Commands:\\n\";
";
    $out .= "    // COMMANDS_START
";
    $commands = [];
    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if (in_array(strtolower($method), ['parameters', 'summary', 'description', 'servers'])) continue;
            $opId = $operation['operationId'] ?? strtolower($method) . preg_replace('/[^a-zA-Z0-9]/', '', $path);
            $commands[$opId] = $operation;
            $out .= "    echo \"  $opId\\n\";
";
        }
    }
    $out .= "    // COMMANDS_END
";
    $out .= "    exit(0);
";
    $out .= "}

";
    
    foreach ($commands as $opId => $operation) {
        $out .= "if (\$command === '$opId') {
";
        $out .= "    \$params = [];
";
        $out .= "    \$body = [];
";
        $out .= "    for (\$i = 2; \$i < \$argc; \$i++) {
";
        $out .= "        if (strpos(\$argv[\$i], '--') === 0 && isset(\$argv[\$i+1])) {
";
        $out .= "            \$key = substr(\$argv[\$i], 2);
";
        $out .= "            \$params[\$key] = \$argv[\$i+1];
";
        $out .= "            \$i++;
";
        $out .= "        }
";
        $out .= "    }
";
        $out .= "    \$response = \$client->$opId(\$params, \$body);
";
        $out .= "    echo json_encode(\$response, JSON_PRETTY_PRINT) . \"\\n\";
";
        $out .= "    exit(0);
";
        $out .= "}

";
    }
    
    $out .= "echo \"Unknown command: \$command\\n\";
exit(1);
";
    
    return $out;
}