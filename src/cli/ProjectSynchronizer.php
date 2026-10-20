<?php

/**
 * Bidirectional codebase and OpenAPI/Arazzo synchronization service.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Synchronizes PHP models, controllers, routes, CLI scripts, mocks, tests, and OpenAPI/Arazzo specifications.
 */
class ProjectSynchronizer
{
    /**
     * Merges top-level OpenAPI metadata from `api_metadata.php` into an OpenAPI array.
     *
     * @param array<string, mixed> $openapi Target OpenAPI specification array.
     * @param string $metadataFile Path to `api_metadata.php`.
     * @return array<string, mixed> Updated OpenAPI specification array.
     */
    public static function applyApiMetadata(array $openapi, string $metadataFile): array
    {
        if (file_exists($metadataFile)) {
            $meta = \Cdd\Mocks\parse((string) file_get_contents($metadataFile));
            if (is_array($meta)) {
                foreach ($meta as $k => $v) {
                    $openapi[$k] = is_array($v) && array_key_exists('dataValue', $v) ? $v['dataValue'] : $v;
                }
            }
        }
        return $openapi;
    }

    /**
     * Merges parsed controller or CLI operation metadata into existing OpenAPI paths by matching `operationId`.
     *
     * @param array<string, mixed> $paths Existing OpenAPI Paths map.
     * @param array<string, mixed> $sourceOps Parsed controller or CLI operations.
     * @param string|null $cliPrefix Optional prefix (e.g. `'/cli/'`) when extracting from CLI path items.
     * @return array<string, mixed> Updated OpenAPI Paths map.
     */
    public static function mergeOperationMetadata(array $paths, array $sourceOps, ?string $cliPrefix = null): array
    {
        if (empty($sourceOps)) {
            return $paths;
        }
        foreach ($paths as $path => &$methods) {
            foreach ($methods as $method => &$opData) {
                if (is_array($opData) && isset($opData['operationId'])) {
                    $opId = (string) $opData['operationId'];
                    if ($cliPrefix !== null) {
                        $cliKey = $cliPrefix . $opId;
                        if (isset($sourceOps[$cliKey]['post'])) {
                            $opData = array_replace_recursive($opData, $sourceOps[$cliKey]['post']);
                        }
                    } elseif (isset($sourceOps[$opId])) {
                        $opData = array_replace_recursive($opData, $sourceOps[$opId]);
                    }
                }
            }
        }
        return $paths;
    }

    /**
     * Parses an entire generated or hand-written project directory (both single-file SDK and modular Server layouts) into an OpenAPI array.
     *
     * @param string $dir Root directory of the PHP project.
     * @param array<string, mixed> $openapi Initial OpenAPI specification skeleton.
     * @return array<string, mixed> Populated OpenAPI specification array.
     */
    public static function parseDirectory(string $dir, array $openapi): array
    {
        $openapi = self::applyApiMetadata($openapi, "$dir/src/api_metadata.php");

        if (file_exists("$dir/src/routes.php")) {
            $routes = \Cdd\Routes\parse((string) file_get_contents("$dir/src/routes.php"));
            if (!empty($routes)) {
                $openapi['paths'] = array_replace_recursive((array) ($openapi['paths'] ?? []), $routes);
            }
        }
        if (is_dir("$dir/src/Routes")) {
            $routeFiles = glob("$dir/src/Routes/*.php") ?: [];
            sort($routeFiles);
            foreach ($routeFiles as $rf) {
                $routes = \Cdd\Routes\parse((string) file_get_contents($rf));
                if (!empty($routes)) {
                    $openapi['paths'] = array_replace_recursive((array) ($openapi['paths'] ?? []), $routes);
                }
            }
        }

        if (file_exists("$dir/src/ApiClient.php")) {
            $clientPaths = \Cdd\Client\parse((string) file_get_contents("$dir/src/ApiClient.php"));
            if (!empty($clientPaths)) {
                $openapi['paths'] = array_replace_recursive((array) ($openapi['paths'] ?? []), $clientPaths);
            }
        }

        if (file_exists("$dir/src/ApiController.php")) {
            $controllerOps = \Cdd\Controllers\parse((string) file_get_contents("$dir/src/ApiController.php"));
            $openapi['paths'] = self::mergeOperationMetadata((array) ($openapi['paths'] ?? []), $controllerOps);
        }
        if (is_dir("$dir/src/Controllers")) {
            $ctrlFiles = glob("$dir/src/Controllers/*.php") ?: [];
            sort($ctrlFiles);
            $allCtrlOps = [];
            foreach ($ctrlFiles as $cf) {
                $ctrlOps = \Cdd\Controllers\parse((string) file_get_contents($cf));
                $allCtrlOps = array_replace($allCtrlOps, $ctrlOps);
            }
            if (!empty($allCtrlOps)) {
                $openapi['paths'] = self::mergeOperationMetadata((array) ($openapi['paths'] ?? []), $allCtrlOps);
            }
        }

        if (file_exists("$dir/src/api_cli.php")) {
            $cliOps = \Cdd\Cli\parse((string) file_get_contents("$dir/src/api_cli.php"));
            if (!empty($openapi['paths'])) {
                $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $cliOps, '/cli/');
            } else {
                $openapi['paths'] = $cliOps;
            }
        }

        if (file_exists("$dir/src/Models.php")) {
            $classes = \Cdd\Classes\parse((string) file_get_contents("$dir/src/Models.php"));
            if (!empty($classes)) {
                $openapi['components'] = \Cdd\Components\parse_classes($classes, (array) ($openapi['components'] ?? []));
            }
        }
        if (is_dir("$dir/src/Models")) {
            $modelFiles = glob("$dir/src/Models/*.php") ?: [];
            sort($modelFiles);
            foreach ($modelFiles as $mf) {
                $classes = \Cdd\Classes\parse((string) file_get_contents($mf));
                if (!empty($classes)) {
                    $openapi['components'] = \Cdd\Components\parse_classes($classes, (array) ($openapi['components'] ?? []));
                }
            }
        }

        if (file_exists("$dir/src/mocks.php")) {
            $mocks = \Cdd\Mocks\parse((string) file_get_contents("$dir/src/mocks.php"));
            if (!empty($mocks)) {
                $openapi['components']['examples'] = $mocks;
                $openapi['components']['schemas'] = \Cdd\Components\infer_schemas_from_mocks(
                    $mocks,
                    (array) ($openapi['components']['schemas'] ?? [])
                );
            }
        }
        if (is_dir("$dir/src/Mocks")) {
            $mockFiles = glob("$dir/src/Mocks/*Mock.php") ?: [];
            sort($mockFiles);
            $modularMocks = [];
            foreach ($mockFiles as $mkf) {
                $baseMockName = substr(basename($mkf, '.php'), 0, -4);
                $parsedMock = \Cdd\Mocks\parse("<?php\nreturn ['{$baseMockName}' => " . trim((string) preg_replace('/^<\?php\s*(?:\/\/[^\n]*\n)?\s*return\s+/s', '', rtrim(trim((string) file_get_contents($mkf)), ';'))) . "];\n");
                $modularMocks = array_replace($modularMocks, $parsedMock);
            }
            if (!empty($modularMocks)) {
                $openapi['components']['examples'] = array_replace(
                    (array) ($openapi['components']['examples'] ?? []),
                    $modularMocks
                );
            }
        }

        if (file_exists("$dir/src/ApiServers.php")) {
            $parsedServers = \Cdd\Servers\parse((string) file_get_contents("$dir/src/ApiServers.php"));
            if (!empty($parsedServers)) {
                $openapi['servers'] = $parsedServers;
            }
        }

        if (file_exists("$dir/Webhooks.php")) {
            $webhooks = \Cdd\Webhooks\parse((string) file_get_contents("$dir/Webhooks.php"));
            if (!empty($webhooks)) {
                $openapi['webhooks'] = $webhooks;
            }
        }

        $testedPaths = [];
        if (file_exists("$dir/src/ComposableTests.php")) {
            $testedPaths = \Cdd\Tests\parse((string) file_get_contents("$dir/src/ComposableTests.php"));
        } elseif (file_exists("$dir/src/ApiTests.php")) {
            $testedPaths = \Cdd\Tests\parse((string) file_get_contents("$dir/src/ApiTests.php"));
        }
        if (is_dir("$dir/tests/Routes")) {
            $testFiles = glob("$dir/tests/Routes/*Test.php") ?: [];
            sort($testFiles);
            foreach ($testFiles as $tf) {
                $tp = \Cdd\Tests\parse((string) file_get_contents($tf));
                $testedPaths = array_replace_recursive($testedPaths, $tp);
            }
        }
        foreach ($testedPaths as $testedMethod => $pathMap) {
            if (is_array($pathMap)) {
                foreach (array_keys($pathMap) as $testedPath) {
                    if (!isset($openapi['paths'][$testedPath][$testedMethod])) {
                        $openapi['paths'][$testedPath][$testedMethod] = [
                            'operationId' => $testedMethod . preg_replace('/[^a-zA-Z0-9]/', '', (string) $testedPath),
                            'responses' => ['200' => ['description' => 'Successful operation']],
                        ];
                    }
                }
            }
        }

        return $openapi;
    }

    /**
     * Runs the bidirectional `sync` command.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runSync(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $dir = '';
        $truth = '';
        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $dir = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-t' || $argv[$i] === '--truth') && isset($argv[$i + 1])) {
                $truth = $argv[$i + 1];
                $i++;
            } elseif ($dir === '') {
                $dir = $resolvePath($argv[$i]);
            }
        }

        if (!$dir || !is_dir($dir)) {
            echo "Error: Directory not found.
";
            return 1;
        }

        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Synced API', 'version' => '0.0.4'],
            'paths' => [],
            'components' => ['schemas' => []],
        ];

        if ($truth === 'spec' && file_exists("$dir/openapi.json")) {
            $openapi = \Cdd\Openapi\parse((string) file_get_contents("$dir/openapi.json"));
        }

        $openapi = self::parseDirectory($dir, $openapi);

        $options = [];
        if (file_exists("$dir/src/ComposableTests.php") || file_exists("$dir/src/ApiTests.php") || file_exists("$dir/src/mocks.php") || is_dir("$dir/tests/Routes")) {
            $options['tests'] = true;
        }
        $json = \Cdd\Openapi\emit($openapi, $dir, $options);
        file_put_contents("$dir/openapi.json", $json);

        if (file_exists("$dir/arazzo.json") || file_exists("$dir/src/ApiWorkflows.php")) {
            $arazzo = [];
            if ($truth === 'spec' && file_exists("$dir/arazzo.json")) {
                $arazzo = \Cdd\Arazzo\parse((string) file_get_contents("$dir/arazzo.json"));
            } elseif (file_exists("$dir/src/ApiWorkflows.php")) {
                $arazzo = \Cdd\ArazzoClient\parse((string) file_get_contents("$dir/src/ApiWorkflows.php"));
            } elseif (file_exists("$dir/arazzo.json")) {
                $arazzo = \Cdd\Arazzo\parse((string) file_get_contents("$dir/arazzo.json"));
            }
            if (!empty($arazzo)) {
                $arazzoJson = \Cdd\Arazzo\emit($arazzo, $dir, ['subcommand' => 'all']);
                file_put_contents("$dir/arazzo.json", $arazzoJson);
            }
        }

        echo "Synchronized codebase in $dir successfully.
";
        return 0;
    }

    /**
     * Runs the `to_openapi` subcommand.
     *
     * @param array<int, string> $argv CLI arguments.
     * @param callable(string): string $resolvePath Path resolution helper.
     * @return int Exit status code.
     */
    public static function runToOpenApi(array $argv, callable $resolvePath): int
    {
        $argc = count($argv);
        $file = '';
        $outFile = '';
        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '-i' || $argv[$i] === '--input') && isset($argv[$i + 1])) {
                $file = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif (($argv[$i] === '-o' || $argv[$i] === '--output') && isset($argv[$i + 1])) {
                $outFile = $resolvePath($argv[$i + 1]);
                $i++;
            } elseif ($file === '') {
                $file = $resolvePath($argv[$i]);
            }
        }

        if (!$file || !file_exists($file)) {
            echo "Error: File not found.
";
            return 1;
        }

        $openapi = [
            'openapi' => '3.2.0',
            'info' => [
                'title' => 'Parsed API',
                'version' => '0.0.4',
            ],
            'paths' => [],
            'components' => ['schemas' => []],
        ];

        if (is_dir($file)) {
            $openapi = self::parseDirectory($file, $openapi);
        } else {
            $code = (string) file_get_contents($file);
            $dir = dirname($file);
            $openapi = self::applyApiMetadata($openapi, "$dir/src/api_metadata.php");

            if (strpos($code, 'curl_exec') !== false) {
                $openapi['paths'] = \Cdd\Client\parse($code);
            } else {
                $routes = \Cdd\Routes\parse($code);
                if (!empty($routes)) {
                    $openapi['paths'] = $routes;
                }

                if (!empty($openapi['paths'])) {
                    $controllerOps = \Cdd\Controllers\parse($code);
                    $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $controllerOps);

                    $cliOps = \Cdd\Cli\parse($code);
                    $openapi['paths'] = self::mergeOperationMetadata((array) $openapi['paths'], $cliOps, '/cli/');
                }

                $servers = \Cdd\Servers\parse($code);
                if (!empty($servers)) {
                    $openapi['servers'] = $servers;
                }

                $classes = \Cdd\Classes\parse($code);
                if (!empty($classes)) {
                    $openapi['components'] = \Cdd\Components\parse_classes($classes, (array) $openapi['components']);
                }
            }
        }

        if (empty((array) ($openapi['paths'] ?? []))) {
            unset($openapi['paths']);
        }
        if (empty((array) ($openapi['components']['schemas'] ?? []))) {
            unset($openapi['components']['schemas']);
        }
        if (empty((array) ($openapi['components'] ?? []))) {
            unset($openapi['components']);
        }

        $outStr = json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "
";
        if ($outFile !== '') {
            file_put_contents($outFile, $outStr);
            echo "Emitted OpenAPI to $outFile
";
        } else {
            echo $outStr;
        }
        return 0;
    }
}
