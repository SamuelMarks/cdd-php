<?php

/**
 * Generates language-specific SDK documentation snippets (`docs.json`) from OpenAPI specifications.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Generates structured SDK code snippets and wrapper metadata for OpenAPI operations.
 */
class DocsJsonGenerator
{
    /**
     * Builds operation snippet entries for a Path Item map (`paths` or `webhooks`).
     *
     * @param array<string, mixed> $pathMap Map of route path or webhook name to Path Item definitions.
     * @param bool $noImports Whether to omit import statements.
     * @param bool $noWrapping Whether to omit wrapper initialization/closing statements.
     * @param string $fallbackPrefix Optional prefix for fallback operationId generation.
     * @return array<int, array<string, mixed>> List of operation documentation records.
     */
    private static function buildPathOperations(
        array $pathMap,
        bool $noImports,
        bool $noWrapping,
        string $fallbackPrefix = ''
    ): array {
        $operations = [];
        $nonMethodKeys = ['parameters', 'summary', 'description', 'servers', '$ref'];

        foreach ($pathMap as $path => $methods) {
            if (!is_array($methods)) {
                continue;
            }
            $methodEntries = [];
            foreach ($methods as $method => $operation) {
                $methodLower = strtolower((string) $method);
                if (in_array($methodLower, $nonMethodKeys, true)) {
                    continue;
                }
                if ($method === 'additionalOperations' && is_array($operation)) {
                    foreach ($operation as $addMethod => $addOp) {
                        if (is_array($addOp)) {
                            $methodEntries[(string) $addMethod] = $addOp;
                        }
                    }
                    continue;
                }
                if (is_array($operation)) {
                    $methodEntries[(string) $method] = $operation;
                }
            }

            foreach ($methodEntries as $method => $operation) {
                $defaultOpId = ($fallbackPrefix !== '' ? $fallbackPrefix . '-' : '')
                    . strtolower($method)
                    . preg_replace('/[^a-zA-Z0-9]/', '', (string) $path);
                $opId = (string) ($operation['operationId'] ?? $defaultOpId);

                $camelOpId = (string) preg_replace_callback('/[-_](.)/', static function (array $m): string {
                    return strtoupper($m[1]);
                }, $opId);

                $params = [];
                if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                    foreach ($operation['parameters'] as $p) {
                        if (is_array($p)) {
                            $name = (string) ($p['name'] ?? 'param');
                            $params[] = "'$name' => 'value'";
                        }
                    }
                }
                if (isset($operation['requestBody'])) {
                    $params[] = "'body' => [...]";
                }
                $paramStr = empty($params) ? '' : '[' . implode(', ', $params) . ']';

                $snippet = "\$response = \$client->{$camelOpId}($paramStr);\nprint_r(\$response);";

                $codeBlock = [
                    'snippet' => $snippet,
                ];

                if (!$noImports) {
                    $codeBlock['imports'] = "<?php\nrequire_once 'vendor/autoload.php';\nuse Api\\ApiClient;";
                }

                if (!$noWrapping) {
                    $codeBlock['wrapper_start'] = "\$client = new ApiClient('https://api.example.com');";
                    $codeBlock['wrapper_end'] = '';
                }

                $operations[] = [
                    'method' => strtoupper($method),
                    'path' => (string) $path,
                    'operationId' => $opId,
                    'code' => $codeBlock,
                ];
            }
        }

        return $operations;
    }

    /**
     * Builds workflow snippet entries for an Arazzo `workflows` list.
     *
     * @param array<int, mixed> $workflows List of Arazzo Workflow Objects.
     * @param bool $noImports Whether to omit import statements.
     * @param bool $noWrapping Whether to omit wrapper initialization/closing statements.
     * @return array<int, array<string, mixed>> List of workflow operation documentation records.
     */
    private static function buildWorkflowOperations(
        array $workflows,
        bool $noImports,
        bool $noWrapping
    ): array {
        $operations = [];

        foreach ($workflows as $wf) {
            if (!is_array($wf)) {
                continue;
            }
            $wfId = (string) ($wf['workflowId'] ?? 'workflow');
            $methodName = \Cdd\ArazzoClient\toCamelCaseIdentifier($wfId);

            $inputs = [];
            if (isset($wf['inputs']['properties']) && is_array($wf['inputs']['properties'])) {
                foreach (array_keys($wf['inputs']['properties']) as $propName) {
                    $inputs[] = "'" . (string) $propName . "' => 'value'";
                }
            }
            $inputStr = empty($inputs) ? '' : '[' . implode(', ', $inputs) . ']';

            $snippet = "\$outputs = \$workflows->{$methodName}({$inputStr});\nprint_r(\$outputs);";

            $codeBlock = [
                'snippet' => $snippet,
            ];

            if (!$noImports) {
                $codeBlock['imports'] = "<?php\nrequire_once 'vendor/autoload.php';\nuse Api\\ApiClient;\nuse Api\\ApiWorkflows;";
            }

            if (!$noWrapping) {
                $codeBlock['wrapper_start'] = "\$client = new ApiClient('https://api.example.com');\n\$workflows = new ApiWorkflows(\$client);";
                $codeBlock['wrapper_end'] = '';
            }

            $operations[] = [
                'method' => 'WORKFLOW',
                'path' => '/workflows/' . $wfId,
                'operationId' => $wfId,
                'code' => $codeBlock,
            ];
        }

        return $operations;
    }

    /**
     * Reads an environment variable from `$_ENV`, `$_SERVER`, or `getenv()`.
     *
     * @param string $name The environment variable name.
     * @return string|null The environment variable value or null if unset/empty.
     */
    private static function getEnvVar(string $name): ?string
    {
        if (isset($_ENV[$name]) && (string) $_ENV[$name] !== '') {
            return (string) $_ENV[$name];
        }
        if (isset($_SERVER[$name]) && (string) $_SERVER[$name] !== '') {
            return (string) $_SERVER[$name];
        }
        $val = getenv($name);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return null;
    }

    /**
     * Checks whether a boolean environment variable flag is truthy.
     *
     * @param string $name The environment variable name.
     * @return bool True if the variable is set to a truthy string (`1`, `true`, `yes`, `on`).
     */
    private static function isEnvFlagEnabled(string $name): bool
    {
        $val = self::getEnvVar($name);
        if ($val === null) {
            return false;
        }
        return in_array(strtolower(trim($val)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Runs the `to_docs_json` subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function run(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $file = '';
        $outFileDocs = '';
        $noImports = self::isEnvFlagEnabled('CDD_NO_IMPORTS');
        $noWrapping = self::isEnvFlagEnabled('CDD_NO_WRAPPING');

        $envInput = self::getEnvVar('CDD_INPUT');
        if ($envInput !== null) {
            $file = $resolvePath($envInput);
        }

        $envOutput = self::getEnvVar('CDD_OUTPUT');
        if ($envOutput !== null) {
            $outFileDocs = $resolvePath($envOutput);
        }

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

        $spec = json_decode((string) file_get_contents($file), true);
        if (!is_array($spec) || empty($spec)) {
            fwrite(STDERR, "Error parsing JSON spec.\n");
            return 1;
        }

        $operations = [];
        if (isset($spec['paths']) && is_array($spec['paths'])) {
            $operations = array_merge(
                $operations,
                self::buildPathOperations($spec['paths'], $noImports, $noWrapping)
            );
        }

        if (isset($spec['webhooks']) && is_array($spec['webhooks'])) {
            $operations = array_merge(
                $operations,
                self::buildPathOperations($spec['webhooks'], $noImports, $noWrapping, 'webhook')
            );
        }

        if (isset($spec['workflows']) && is_array($spec['workflows'])) {
            $operations = array_merge(
                $operations,
                self::buildWorkflowOperations($spec['workflows'], $noImports, $noWrapping)
            );
        }

        $result = [
            [
                'language' => 'php',
                'operations' => $operations,
            ],
        ];

        $outStr = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
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
}
