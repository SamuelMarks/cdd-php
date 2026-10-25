<?php

/**
 * Emits OpenAPI 3.2.0 or Swagger 2.0 JSON and scaffolds generated PHP projects.
 *
 * @package Cdd\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Emits an OpenAPI array structure as JSON string and coordinates generation
 * of the associated PHP code representations into the given directory.
 *
 * @param array<string, mixed> $openapi The parsed OpenAPI spec
 * @param string|null $outDir The directory to emit PHP code into
 * @param array<string, mixed> $options Generation options ('subcommand', 'tests', 'target_version', 'no_installable_package', 'no_github_actions', 'mcp')
 * @return string The JSON representation
 * @throws \Cdd\Exceptions\CddException If JSON encoding fails
 */
function emit(array $openapi, ?string $outDir = null, array $options = []): string
{
    if (!isset($openapi['openapi'])) {
        $openapi['openapi'] = '3.2.0';
    }

    if (!isset($openapi['info'])) {
        $openapi['info'] = [
            'title' => 'Default API',
            'version' => '0.0.4',
        ];
    }

    if (!isset($openapi['paths']) && !isset($openapi['components']) && !isset($openapi['webhooks'])) {
        $openapi['paths'] = (object) [];
    }

    if ($outDir) {
        $srcDir = "$outDir/src";
        if (!is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }

        $serverCode = "<?php

class ApiServers {
";
        if (isset($openapi['servers'])) {
            $serverCode .= \Cdd\Servers\emit($openapi['servers']);
        }
        $serverCode .= "}
";
        file_put_contents("$srcDir/ApiServers.php", $serverCode);

        $metadata = [];
        foreach (['info', 'jsonSchemaDialect', 'externalDocs', 'tags', 'security'] as $key) {
            if (isset($openapi[$key])) {
                $metadata[$key] = $openapi[$key];
            }
        }
        if (!empty($metadata)) {
            $metadataCode = "<?php

// Auto-generated API metadata

return " . var_export($metadata, true) . ";
";
            file_put_contents("$srcDir/api_metadata.php", $metadataCode);
        }

        $subcommand = (string) ($options['subcommand'] ?? '');

        if ($subcommand === 'to_server') {
            if (!isset($openapi['paths']['/mcp/sse'])) {
                $openapi['paths']['/mcp/sse'] = [
                    'get' => [
                        'operationId' => 'mcp_sse',
                        'description' => 'MCP SSE Endpoint',
                        'responses' => [
                            '200' => ['description' => 'SSE stream'],
                        ],
                    ],
                ];
            }
            if (!isset($openapi['paths']['/mcp/message'])) {
                $openapi['paths']['/mcp/message'] = [
                    'post' => [
                        'operationId' => 'mcp_message',
                        'description' => 'MCP Message Endpoint',
                        'requestBody' => [
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/JSONRPCRequest']]],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Message processed', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/JSONRPCResponse']]]],
                        ],
                    ],
                ];
            }
        }

        if (isset($openapi['paths'])) {
            if ($subcommand === 'to_server') {
                $controllersDir = "$srcDir/Controllers";
                if (!is_dir($controllersDir)) {
                    mkdir($controllersDir, 0777, true);
                }
                $controllers = \Cdd\Paths\emit_modular((array) $openapi['paths']);
                foreach ($controllers as $filename => $code) {
                    file_put_contents("$controllersDir/$filename", $code);
                }

                $routeFiles = \Cdd\Routes\emit_modular((array) $openapi['paths']);
                $routesDir = "$srcDir/Routes";
                if (!is_dir($routesDir)) {
                    mkdir($routesDir, 0777, true);
                }
                foreach ($routeFiles as $filename => $code) {
                    file_put_contents("$routesDir/$filename", $code);
                }

                $mainRouteCode = "<?php

foreach (glob(__DIR__ . '/Routes/*.php') as \$file) {
    require_once \$file;
}
";
                file_put_contents("$srcDir/routes.php", $mainRouteCode);
            } else {
                $controllerCode = \Cdd\Paths\emit((array) $openapi['paths'], file_exists("$srcDir/ApiController.php") ? (string) file_get_contents("$srcDir/ApiController.php") : '');
                file_put_contents("$srcDir/ApiController.php", $controllerCode);

                $routeCode = \Cdd\Routes\emit((array) $openapi['paths'], file_exists("$srcDir/routes.php") ? (string) file_get_contents("$srcDir/routes.php") : '');
                file_put_contents("$srcDir/routes.php", $routeCode);
            }

            $securityDefinitions = $openapi['securityDefinitions'] ?? ($openapi['components']['securitySchemes'] ?? []);
            $clientCode = \Cdd\Client\emit_class((array) $openapi['paths'], file_exists("$srcDir/ApiClient.php") ? (string) file_get_contents("$srcDir/ApiClient.php") : '', $securityDefinitions);
            file_put_contents("$srcDir/ApiClient.php", $clientCode);
        }

        if ($subcommand === 'to_server' || $subcommand === 'to_sdk' || $subcommand === 'to_sdk_cli') {
            if (!isset($openapi['components'])) {
                $openapi['components'] = [];
            }
            if (!isset($openapi['components']['schemas'])) {
                $openapi['components']['schemas'] = [];
            }
            require_once __DIR__ . '/mcp_schema.php';
            $mcpSchemas = get_mcp_schema();
            foreach ($mcpSchemas as $name => $schema) {
                if (!isset($openapi['components']['schemas'][$name])) {
                    $openapi['components']['schemas'][$name] = $schema;
                }
            }
        }

        if (isset($openapi['components'])) {
            if ($subcommand === 'to_server') {
                emitServerProject($openapi, $outDir, $srcDir);
            } else {
                $componentsCode = \Cdd\Components\emit($openapi['components'], file_exists("$srcDir/Models.php") ? (string) file_get_contents("$srcDir/Models.php") : '');
                file_put_contents("$srcDir/Models.php", $componentsCode);
            }
        }

        $tests = (bool) ($options['tests'] ?? false);

        if ($tests && isset($openapi['components']['examples'])) {
            if ($subcommand === 'to_server') {
                $mockFiles = \Cdd\Mocks\emit_modular($openapi['components']['examples']);
                $mocksDir = "$srcDir/Mocks";
                if (!is_dir($mocksDir)) {
                    mkdir($mocksDir, 0777, true);
                }
                foreach ($mockFiles as $filename => $code) {
                    file_put_contents("$mocksDir/$filename", $code);
                }
                $mainMockCode = "<?php

\$mocks = [];
foreach (glob(__DIR__ . '/Mocks/*Mock.php') as \$file) {
    \$name = basename(\$file, 'Mock.php');
    \$mocks[\$name] = require \$file;
}
return \$mocks;
";
                file_put_contents("$srcDir/mocks.php", $mainMockCode);
            } else {
                $mocksCode = \Cdd\Mocks\emit($openapi['components']['examples'], file_exists("$srcDir/mocks.php") ? (string) file_get_contents("$srcDir/mocks.php") : '');
                file_put_contents("$srcDir/mocks.php", $mocksCode);
            }
        }

        if (isset($openapi['webhooks']) && function_exists('\Cdd\Webhooks\emit')) {
            $webhooksCode = \Cdd\Webhooks\emit($openapi['webhooks'], file_exists("$srcDir/Webhooks.php") ? (string) file_get_contents("$srcDir/Webhooks.php") : '');
            if ($webhooksCode) {
                file_put_contents("$srcDir/Webhooks.php", $webhooksCode);
            }
        }

        if ($tests) {
            $testsDir = "$outDir/tests";
            if (!is_dir($testsDir)) {
                mkdir($testsDir, 0777, true);
            }

            if ($subcommand === 'to_server') {
                $routeTestsDir = "$testsDir/Routes";
                if (!is_dir($routeTestsDir)) {
                    mkdir($routeTestsDir, 0777, true);
                }
                $routeTests = \Cdd\Tests\emit_modular((array) ($openapi['paths'] ?? []));
                foreach ($routeTests as $filename => $code) {
                    file_put_contents("$routeTestsDir/$filename", $code);
                }
            } else {
                $statusMap = [];
                if (isset($openapi['paths'])) {
                    foreach ((array) $openapi['paths'] as $path => $methods) {
                        foreach ((array) $methods as $method => $operation) {
                            if (!is_array($operation) || in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers', 'additionaloperations'], true)) {
                                continue;
                            }
                            $statusMap[strtolower((string) $method) . ' ' . (string) $path] = \Cdd\Tests\resolve_expected_status($operation);
                        }
                    }
                }
                $exportedStatusMap = var_export($statusMap, true);
                $phpunitCode = "<?php

// Auto-generated tests

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ApiTests extends TestCase {
    protected function call(string \$method, string \$path, array \$body = []): object {
        \$statusMap = {$exportedStatusMap};
        \$key = strtolower(\$method) . ' ' . \$path;
        \$status = \$statusMap[\$key] ?? 200;
        return new class(\$status, \$method, \$path, \$body) {
            private int \$status;
            private string \$method;
            private string \$path;
            private array \$body;
            public function __construct(int \$status, string \$method, string \$path, array \$body) {
                \$this->status = \$status;
                \$this->method = \$method;
                \$this->path = \$path;
                \$this->body = \$body;
            }
            public function status(): int { return \$this->status; }
            public function method(): string { return \$this->method; }
            public function path(): string { return \$this->path; }
            public function body(): array { return \$this->body; }
        };
    }

";
                $composableCode = "<?php

// Auto-generated tests

return [
";

                if (isset($openapi['paths'])) {
                    foreach ((array) $openapi['paths'] as $path => $methods) {
                        foreach ($methods as $method => $operation) {
                            if (!is_array($operation) || in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers', 'additionaloperations'], true)) {
                                continue;
                            }
                            $phpunitCode .= \Cdd\Tests\emit((string) $method, (string) $path, $operation, false) . "
";
                            $composableCode .= \Cdd\Tests\emit((string) $method, (string) $path, $operation, true) . "
";
                        }
                    }
                }

                $phpunitCode .= "}
";
                $composableCode .= "];
";

                file_put_contents("$testsDir/ApiTests.php", $phpunitCode);
                file_put_contents("$testsDir/ComposableTests.php", $composableCode);
            }

            if ($subcommand === 'to_server' && isset($openapi['components']['schemas'])) {
                $mockTests = \Cdd\Tests\emit_mock_tests($openapi['components']['schemas']);
                foreach ($mockTests as $filename => $content) {
                    $targetPath = "$testsDir/$filename";
                    $targetDir = dirname($targetPath);
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    file_put_contents($targetPath, $content);
                }
            }
        }

        if (($subcommand === 'to_sdk' || $subcommand === 'to_sdk_cli') && $tests) {
            emitSdkIntegrationTests($openapi, "$outDir/tests");
        }

        $noInstallablePackage = (bool) ($options['no_installable_package'] ?? false);
        $noGithubActions = (bool) ($options['no_github_actions'] ?? false);
        emitProjectScaffolding($outDir, $subcommand, $noInstallablePackage, $noGithubActions);
    }

    $targetVersion = (string) ($options['target_version'] ?? '3.2.0');
    $openapiToEncode = $targetVersion === '2.0'
        ? downgradeOpenApi3ToSwagger2($openapi)
        : $openapi;

    $json = json_encode($openapiToEncode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw \Cdd\Exceptions\CddException::jsonEncode('Failed to encode OpenAPI array to JSON: ' . json_last_error_msg());
    }

    return $json;
}
