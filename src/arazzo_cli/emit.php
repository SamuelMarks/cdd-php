<?php

/**
 * Emits standalone PHP CLI workflow runner scripts (`arazzo_cli.php`) that reuse `ApiWorkflows` and `ApiClient`.
 *
 * @package Cdd\ArazzoCli
 */

declare(strict_types=1);

namespace Cdd\ArazzoCli;

/**
 * Emits a PHP CLI workflow runner script from a normalized Arazzo 1.1.0 specification.
 *
 * @param array<string, mixed> $arazzo The normalized Arazzo specification array.
 * @return string Generated `arazzo_cli.php` source code.
 */
function emit(array $arazzo): string
{
    $info = is_array($arazzo['info'] ?? null) ? $arazzo['info'] : ['title' => 'Arazzo CLI', 'version' => '1.0.0'];
    $title = addslashes((string) ($info['title'] ?? 'Arazzo CLI'));
    $version = addslashes((string) ($info['version'] ?? '1.0.0'));
    $arazzoVer = addslashes((string) ($arazzo['arazzo'] ?? '1.1.0'));
    $summary = addslashes((string) ($info['summary'] ?? ''));
    $description = addslashes((string) ($info['description'] ?? ''));
    $specJson = (string) json_encode($arazzo, JSON_UNESCAPED_SLASHES);

    $lines = [];
    $lines[] = '<?php';
    $lines[] = '';
    $lines[] = '/**';
    $lines[] = ' * Auto-generated Arazzo Workflow CLI Runner';
    $lines[] = ' * Usage: php arazzo_cli.php <workflowId> [options]';
    $lines[] = ' *';
    $lines[] = ' * @package Api\Cli';
    $lines[] = ' * @arazzo-spec ' . $specJson;
    $lines[] = ' */';
    $lines[] = '';
    $lines[] = 'declare(strict_types=1);';
    $lines[] = '';
    $lines[] = "if (file_exists(__DIR__ . '/ApiClient.php')) {";
    $lines[] = "    require_once __DIR__ . '/ApiClient.php';";
    $lines[] = '}';
    $lines[] = "require_once __DIR__ . '/ApiWorkflows.php';";
    $lines[] = '';
    $lines[] = 'use Api\ApiWorkflows;';
    $lines[] = '';
    $lines[] = "\$defaultClient = class_exists(Api\\ApiClient::class) ? new Api\\ApiClient('http://localhost') : null;";
    $lines[] = '$workflows = new ApiWorkflows($defaultClient);';
    $lines[] = "\$command = \$argv[1] ?? '--help';";
    $lines[] = '';
    $lines[] = "if (\$command === '--version' || \$command === '-V') {";
    $lines[] = "    echo '{$title} v{$version} (Arazzo {$arazzoVer})' . PHP_EOL;";
    $lines[] = '    exit(0);';
    $lines[] = '}';
    $lines[] = '';
    $lines[] = "if (\$command === '--help' || \$command === '-h') {";
    $lines[] = "    echo '{$title} (v{$version})' . PHP_EOL;";
    if ($summary !== '') {
        $lines[] = "    echo '{$summary}' . PHP_EOL;";
    }
    if ($description !== '') {
        $lines[] = "    echo '{$description}' . PHP_EOL;";
    }
    $lines[] = "    echo 'Usage: php arazzo_cli.php <workflowId> [options]' . PHP_EOL;";
    foreach ($arazzo['workflows'] ?? [] as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $wfSum = addslashes((string) ($wf['summary'] ?? $wf['description'] ?? "Run workflow {$wfId}"));
        $lines[] = "    echo '  " . str_pad($wfId, 28) . " {$wfSum}' . PHP_EOL;";
    }
    $lines[] = '    exit(0);';
    $lines[] = '}';
    $lines[] = '';

    foreach ($arazzo['workflows'] ?? [] as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $wfSum = addslashes((string) ($wf['summary'] ?? "Execute {$wfId}"));
        $props = is_array($wf['inputs']['properties'] ?? null) ? $wf['inputs']['properties'] : [];

        $lines[] = "if (\$command === '{$wfId}') {";
        $lines[] = "    if (in_array('--help', \$argv, true) || in_array('-h', \$argv, true)) {";
        $lines[] = "        echo 'Workflow: {$wfId} - {$wfSum}' . PHP_EOL;";
        foreach ($props as $pName => $pSchema) {
            $pType = is_array($pSchema) ? (string) ($pSchema['type'] ?? 'string') : 'string';
            $lines[] = "        echo '  --{$pName} <{$pType}>' . PHP_EOL;";
        }
        $lines[] = '        exit(0);';
        $lines[] = '    }';
        $lines[] = '    $inputs = [];';
        $lines[] = '    $dryRun = false;';
        $lines[] = '    $onlyStep = null;';
        $lines[] = '    for ($i = 2; $i < $argc; $i++) {';
        $lines[] = '        $arg = $argv[$i];';
        $lines[] = "        if (\$arg === '--dry-run') { \$dryRun = true; continue; }";
        $lines[] = "        if (\$arg === '--step' && isset(\$argv[\$i + 1])) { \$onlyStep = \$argv[++\$i]; continue; }";
        $lines[] = "        if (str_starts_with(\$arg, '--') && isset(\$argv[\$i + 1])) {";
        $lines[] = '            $k = substr($arg, 2);';
        $lines[] = '            $v = $argv[++$i];';
        $lines[] = "            \$inputs[\$k] = is_numeric(\$v) ? (str_contains(\$v, '.') ? (float) \$v : (int) \$v) : \$v;";
        $lines[] = '        }';
        $lines[] = '    }';
        $lines[] = "    \$result = \$workflows->executeWorkflow('{$wfId}', \$inputs, \$onlyStep, \$dryRun);";
        $lines[] = '    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;';
        $lines[] = '    exit(0);';
        $lines[] = '}';
        $lines[] = '';
    }

    $lines[] = "fwrite(STDERR, 'Unknown workflow command: ' . \$command . PHP_EOL);";
    $lines[] = 'exit(1);';
    $lines[] = '';

    return implode("
", $lines);
}
