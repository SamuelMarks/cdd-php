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
    $title = (string) ($info['title'] ?? 'Arazzo CLI');
    $version = (string) ($info['version'] ?? '1.0.0');
    $arazzoVer = (string) ($arazzo['arazzo'] ?? '1.1.0');
    $summary = (string) ($info['summary'] ?? '');
    $description = (string) ($info['description'] ?? '');
    $sources = is_array($arazzo['sourceDescriptions'] ?? null) ? $arazzo['sourceDescriptions'] : [];
    $components = is_array($arazzo['components'] ?? null) ? $arazzo['components'] : [];
    $rootExt = \Cdd\Arazzo\extractExtensions($arazzo);

    $cliMeta = [
        'arazzo' => $arazzoVer,
        'info' => $info,
        'sourceDescriptions' => $sources,
    ];
    if (isset($arazzo['$self']) && is_string($arazzo['$self'])) {
        $cliMeta['$self'] = $arazzo['$self'];
    }
    if (!empty($components)) {
        $cliMeta['components'] = $components;
    }
    foreach ($rootExt as $extK => $extV) {
        $cliMeta[$extK] = $extV;
    }

    $lines = [];
    $lines[] = '<?php';
    $lines[] = '';
    $lines[] = '/**';
    $lines[] = ' * Auto-generated Arazzo Workflow CLI Runner';
    $lines[] = ' * Usage: php arazzo_cli.php <workflowId> [options]';
    $lines[] = ' *';
    $lines[] = ' * @package Api\Cli';
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
    $lines[] = '$cliMetadata = ' . var_export($cliMeta, true) . ';';
    $lines[] = "\$defaultClient = class_exists(Api\ApiClient::class) ? new Api\ApiClient('http://localhost') : null;";
    $lines[] = '$workflows = new ApiWorkflows($defaultClient);';
    $lines[] = "\$command = \$argv[1] ?? '--help';";
    $lines[] = '';
    $lines[] = "if (\$command === '--version' || \$command === '-V') {";
    $lines[] = '    echo ' . var_export("{$title} v{$version} (Arazzo {$arazzoVer})", true) . ' . PHP_EOL;';
    $lines[] = '    exit(0);';
    $lines[] = '}';
    $lines[] = '';
    $lines[] = "if (\$command === '--help' || \$command === '-h') {";
    $lines[] = '    echo ' . var_export("{$title} (v{$version})", true) . ' . PHP_EOL;';
    if ($summary !== '') {
        $lines[] = '    echo ' . var_export($summary, true) . ' . PHP_EOL;';
    }
    if ($description !== '') {
        $lines[] = '    echo ' . var_export($description, true) . ' . PHP_EOL;';
    }
    $lines[] = "    echo 'Usage: php arazzo_cli.php <workflowId> [options]' . PHP_EOL;";
    foreach ($arazzo['workflows'] ?? [] as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $wfSum = (string) ($wf['summary'] ?? $wf['description'] ?? "Run workflow {$wfId}");
        $lines[] = '    echo ' . var_export('  ' . str_pad($wfId, 28) . ' ' . $wfSum, true) . ' . PHP_EOL;';
    }
    $lines[] = '    exit(0);';
    $lines[] = '}';
    $lines[] = '';

    foreach ($arazzo['workflows'] ?? [] as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $wfSum = (string) ($wf['summary'] ?? "Execute {$wfId}");
        $props = is_array($wf['inputs']['properties'] ?? null) ? $wf['inputs']['properties'] : [];
        $wfIdExport = var_export($wfId, true);

        $lines[] = "if (\$command === {$wfIdExport}) {";
        $lines[] = '    $workflowDefinition = ' . var_export($wf, true) . ';';
        $lines[] = "    if (in_array('--help', \$argv, true) || in_array('-h', \$argv, true)) {";
        $lines[] = '        echo ' . var_export("Workflow: {$wfId} - {$wfSum}", true) . ' . PHP_EOL;';
        foreach ($props as $pName => $pSchema) {
            $pType = is_array($pSchema) ? (string) ($pSchema['type'] ?? 'string') : 'string';
            $lines[] = '        echo ' . var_export("  --{$pName} <{$pType}>", true) . ' . PHP_EOL;';
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
        $lines[] = "    \$result = \$workflows->executeWorkflow({$wfIdExport}, \$inputs, \$onlyStep, \$dryRun);";
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
