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
            fwrite(STDERR, "Error: Spec file not found.
");
            return 1;
        }

        $spec = json_decode((string) file_get_contents($file), true);
        if (!$spec) {
            fwrite(STDERR, "Error parsing JSON spec.
");
            return 1;
        }

        $operations = [];
        if (isset($spec['paths'])) {
            foreach ($spec['paths'] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    if (in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers'], true)) {
                        continue;
                    }

                    $opId = $operation['operationId'] ?? strtolower((string) $method) . preg_replace('/[^a-zA-Z0-9]/', '', (string) $path);

                    $camelOpId = (string) preg_replace_callback('/[-_](.)/', static function (array $m): string {
                        return strtoupper($m[1]);
                    }, (string) $opId);

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

                    $snippet = "\$response = \$client->{$camelOpId}($paramStr);
print_r(\$response);";

                    $codeBlock = [
                        'snippet' => $snippet,
                    ];

                    if (!$noImports) {
                        $codeBlock['imports'] = "require_once 'vendor/autoload.php';
use ApiClient;";
                    }

                    if (!$noWrapping) {
                        $codeBlock['wrapper_start'] = "\$client = new ApiClient('https://api.example.com');";
                        $codeBlock['wrapper_end'] = '';
                    }

                    $operations[] = [
                        'method' => strtoupper((string) $method),
                        'path' => $path,
                        'operationId' => $opId,
                        'code' => $codeBlock,
                    ];
                }
            }
        }

        $result = [
            [
                'language' => 'php',
                'operations' => $operations,
            ],
        ];

        $outStr = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "
";
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
