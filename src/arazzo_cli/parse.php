<?php

/**
 * Parses PHP CLI workflow runner scripts (`arazzo_cli.php`) back into Arazzo 1.1.0 specifications.
 *
 * @package Cdd\ArazzoCli
 */

declare(strict_types=1);

namespace Cdd\ArazzoCli;

/**
 * Parses a generated or hand-edited PHP CLI workflow runner into an Arazzo 1.1.0 array.
 *
 * @param string $cliCode The `arazzo_cli.php` source code.
 * @param string|null $sdkWorkflowsCode Optional companion `ApiWorkflows.php` source code.
 * @return array<string, mixed> Reconstructed Arazzo 1.1.0 specification array.
 */
function parse(string $cliCode, ?string $sdkWorkflowsCode = null): array
{
    if (preg_match('/@arazzo-spec\s+(\{[^\r\n]+\})/', $cliCode, $m)) {
        $decoded = json_decode($m[1], true);
        if (is_array($decoded) && isset($decoded['arazzo'], $decoded['workflows'])) {
            return $decoded;
        }
    }
    if ($sdkWorkflowsCode !== null && $sdkWorkflowsCode !== '') {
        $fromSdk = \Cdd\ArazzoClient\parse($sdkWorkflowsCode);
        if (!empty($fromSdk)) {
            return $fromSdk;
        }
    }

    $arazzo = [
        'arazzo' => '1.1.0',
        'info' => [
            'title' => 'CLI Workflows',
            'version' => '1.0.0',
        ],
        'sourceDescriptions' => [
            [
                'name' => 'default',
                'url' => './openapi.json',
                'type' => 'openapi',
            ],
        ],
        'workflows' => [],
    ];

    preg_match_all('/if\s*\(\$command\s*===\s*\x27([^\x27]+)\x27\)/', $cliCode, $matches);
    foreach ($matches[1] ?? [] as $wfId) {
        if (in_array($wfId, ['--help', '-h', '--version', '-V', 'mcp'], true)) {
            continue;
        }
        $arazzo['workflows'][] = [
            'workflowId' => $wfId,
            'steps' => [
                [
                    'stepId' => 'run_' . $wfId,
                    'operationId' => $wfId,
                ],
            ],
        ];
    }

    return $arazzo;
}
