<?php

/**
 * Emits Arazzo 1.1.0 / 1.0.x JSON documents and scaffolds generated PHP SDK, CLI, and Server workflow projects.
 *
 * @package Cdd\Arazzo
 */

declare(strict_types=1);

namespace Cdd\Arazzo;

/**
 * Downgrades an Arazzo 1.1.0 specification array to Arazzo 1.0.1 compatibility.
 *
 * @param array<string, mixed> $arazzo Normalized Arazzo 1.1.0 specification array.
 * @return array<string, mixed> Downgraded Arazzo 1.0.1 array.
 */
function downgradeArazzo11To10(array $arazzo): array
{
    $arazzo['arazzo'] = '1.0.1';
    unset($arazzo['$self']);
    if (isset($arazzo['sourceDescriptions']) && is_array($arazzo['sourceDescriptions'])) {
        foreach ($arazzo['sourceDescriptions'] as &$sd) {
            if (isset($sd['type']) && $sd['type'] === 'asyncapi') {
                $sd['type'] = 'openapi';
            }
        }
        unset($sd);
    }
    if (isset($arazzo['workflows']) && is_array($arazzo['workflows'])) {
        foreach ($arazzo['workflows'] as &$wf) {
            if (isset($wf['steps']) && is_array($wf['steps'])) {
                foreach ($wf['steps'] as &$step) {
                    unset($step['timeout'], $step['correlationId'], $step['action'], $step['dependsOn']);
                    if (isset($step['channelPath'])) {
                        $step['operationPath'] = $step['channelPath'];
                        unset($step['channelPath']);
                    }
                    if (isset($step['parameters']) && is_array($step['parameters'])) {
                        foreach ($step['parameters'] as &$p) {
                            if (isset($p['in']) && $p['in'] === 'querystring') {
                                $p['in'] = 'query';
                            }
                        }
                        unset($p);
                    }
                }
                unset($step);
            }
        }
        unset($wf);
    }
    return $arazzo;
}

/**
 * Emits an Arazzo specification JSON string and optionally scaffolds PHP SDK, CLI, or Server artifacts in `$outDir`.
 *
 * @param array<string, mixed> $arazzo The Arazzo specification array.
 * @param string|null $outDir Optional target directory for PHP project generation.
 * @param array<string, mixed> $options Generation options (`subcommand`, `target_version`, `no_installable_package`, `no_github_actions`, `tests`).
 * @return string Formatted Arazzo JSON document.
 * @throws \Cdd\Exceptions\CddException If JSON encoding fails.
 */
function emit(array $arazzo, ?string $outDir = null, array $options = []): string
{
    if (!isset($arazzo['arazzo'])) {
        $arazzo['arazzo'] = '1.1.0';
    }
    if (!isset($arazzo['info'])) {
        $arazzo['info'] = ['title' => 'Arazzo Workflows', 'version' => '1.0.0'];
    }
    if (!isset($arazzo['sourceDescriptions'])) {
        $arazzo['sourceDescriptions'] = [
            ['name' => 'default', 'url' => './openapi.json', 'type' => 'openapi'],
        ];
    }
    if (!isset($arazzo['workflows'])) {
        $arazzo['workflows'] = [];
    }

    if ($outDir !== null) {
        if (!is_dir($outDir . '/src')) {
            mkdir($outDir . '/src', 0777, true);
        }
        $subcommand = (string) ($options['subcommand'] ?? 'all');
        $noInstallablePackage = (bool) ($options['no_installable_package'] ?? false);
        $noGithubActions = (bool) ($options['no_github_actions'] ?? false);

        file_put_contents($outDir . '/src/arazzo.json', json_encode($arazzo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($subcommand === 'all' || $subcommand === 'to_sdk' || $subcommand === 'to_sdk_cli') {
            file_put_contents($outDir . '/src/ApiWorkflows.php', \Cdd\ArazzoClient\emit($arazzo));
            file_put_contents($outDir . '/src/WorkflowInputs.php', \Cdd\ArazzoClient\emitInputDtos($arazzo));
        }

        if ($subcommand === 'all' || $subcommand === 'to_sdk_cli') {
            file_put_contents($outDir . '/src/arazzo_cli.php', \Cdd\ArazzoCli\emit($arazzo));
        }

        if ($subcommand === 'all' || $subcommand === 'to_server') {
            file_put_contents($outDir . '/src/WorkflowEngine.php', \Cdd\ArazzoServer\emitWorkflowEngine($arazzo));
            if (!is_dir($outDir . '/tests/Workflows')) {
                mkdir($outDir . '/tests/Workflows', 0777, true);
            }
            file_put_contents($outDir . '/tests/Workflows/ArazzoWorkflowTest.php', \Cdd\ArazzoServer\emit($arazzo));
        }

        if (function_exists('\Cdd\Openapi\emitProjectScaffolding')) {
            \Cdd\Openapi\emitProjectScaffolding($outDir, $subcommand, $noInstallablePackage, $noGithubActions);
        }
    }

    $toEncode = $arazzo;
    $targetVersion = $options['target_version'] ?? '1.1.0';
    if (in_array($targetVersion, ['1.0.0', '1.0.1', '1.0'], true)) {
        $toEncode = downgradeArazzo11To10($toEncode);
    }

    return (string) json_encode($toEncode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
