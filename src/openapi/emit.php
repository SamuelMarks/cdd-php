<?php

/**
 * Emits OpenAPI 3.2.0 or Swagger 2.0 JSON and scaffolds generated PHP projects.
 *
 * @package Cdd\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Emits modular server project artifacts (Models, DAOs, Database, Seeder, Exceptions, ServerRunner, README, server.php).
 *
 * @param array<string, mixed> $openapi The parsed OpenAPI specification.
 * @param string $outDir The output project root directory.
 * @param string $srcDir The output `src/` directory.
 * @return void
 */
function emitServerProject(array $openapi, string $outDir, string $srcDir): void
{
    $modelsDir = "$srcDir/Models";
    if (!is_dir($modelsDir)) {
        mkdir($modelsDir, 0777, true);
    }
    foreach ($openapi['components']['schemas'] ?? [] as $schemaName => $schemaDef) {
        $code = \Cdd\Schemas\emit((string) $schemaName, $schemaDef, 'Api\Models') . "
";
        file_put_contents("$modelsDir/$schemaName.php", $code);
    }

    $daosDir = "$srcDir/Daos";
    if (!is_dir($daosDir)) {
        mkdir($daosDir, 0777, true);
    }
    if (isset($openapi['components']['schemas'])) {
        $daosCodeArray = \Cdd\Daos\emit_modular($openapi['components']['schemas']);
        foreach ($daosCodeArray as $filename => $code) {
            file_put_contents("$daosDir/$filename", $code);
        }
    }

    $dbCode = \Cdd\Database\emit($openapi['components']['schemas'] ?? [], 'Api\Database');
    $dbDir = "$srcDir/Database";
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }
    file_put_contents("$dbDir/DatabaseConnection.php", $dbCode);

    $seederCode = \Cdd\Seeder\emit($openapi['components']['schemas'] ?? [], 'Api\Seeder');
    $seederDir = "$srcDir/Seeder";
    if (!is_dir($seederDir)) {
        mkdir($seederDir, 0777, true);
    }
    file_put_contents("$seederDir/DatabaseSeeder.php", $seederCode);

    $excDir = "$srcDir/Exceptions";
    if (!is_dir($excDir)) {
        mkdir($excDir, 0777, true);
    }
    file_put_contents("$excDir/MockServerError.php", "<?php\n\ndeclare(strict_types=1);\n\nnamespace Api\\Exceptions;\n\nclass MockServerError extends \\RuntimeException {}\n");

    $serverRunnerCode = \Cdd\ServerCli\emit('Api');
    file_put_contents("$srcDir/ServerRunner.php", $serverRunnerCode);

    $title = (string) ($openapi['info']['title'] ?? 'Generated API');
    $readmeCode = \Cdd\Readme\emit($title);
    file_put_contents("$outDir/README.md", $readmeCode);

    $serverPhpCode = \Cdd\StandaloneServer\emit();
    file_put_contents("$outDir/server.php", $serverPhpCode);
}

/**
 * Generates a dummy test value matching a given OpenAPI Schema Object.
 *
 * @param array<string, mixed> $schema The OpenAPI Schema Object.
 * @param array<string, mixed> $openapi The full OpenAPI document for `$ref` lookups.
 * @return mixed A representative dummy value matching the schema.
 */
function generateDummySchemaValue(array $schema, array $openapi): mixed
{
    if (isset($schema['$ref']) && strpos((string) $schema['$ref'], '#/components/schemas/') === 0) {
        $refName = substr((string) $schema['$ref'], 21);
        if (isset($openapi['components']['schemas'][$refName])) {
            $schema = $openapi['components']['schemas'][$refName];
        }
    }
    if (isset($schema['enum']) && !empty($schema['enum'])) {
        return $schema['enum'][0];
    }
    $type = $schema['type'] ?? 'string';
    if ($type === 'object' || isset($schema['properties'])) {
        $obj = [];
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propDef) {
                $obj[$propName] = generateDummySchemaValue(is_array($propDef) ? $propDef : [], $openapi);
            }
        } else {
            $obj = ['dummy' => 'data'];
        }
        return $obj;
    }
    if ($type === 'array') {
        $item = generateDummySchemaValue(is_array($schema['items'] ?? null) ? $schema['items'] : [], $openapi);
        return [$item, $item];
    }
    if ($type === 'integer' || $type === 'number') {
        return 1;
    }
    if ($type === 'boolean') {
        return true;
    }
    if ($type === 'string' && isset($schema['format']) && $schema['format'] === 'date-time') {
        return date('Y-m-d\TH:i:s.000\Z');
    }
    return 'test_string';
}

/**
 * Emits the `SdkIntegrationTest.php` test suite for generated SDK projects.
 *
 * @param array<string, mixed> $openapi The parsed OpenAPI specification.
 * @param string $testsDir The output `tests/` directory.
 * @return void
 */
function emitSdkIntegrationTests(array $openapi, string $testsDir): void
{
    $sdkTestCode = "<?php\n\ndeclare(strict_types=1);\n\nuse PHPUnit\\Framework\\TestCase;\nuse Api\\ApiClient;\n\nclass SdkIntegrationTest extends TestCase {\n";
    $sdkTestCode .= "    private ApiClient \$client;

";
    $sdkTestCode .= "    protected function setUp(): void {
";
    $basePath = '';
    if (isset($openapi['servers']) && is_array($openapi['servers']) && count($openapi['servers']) > 0) {
        $serverUrl = (string) $openapi['servers'][0]['url'];
        $parsedUrl = parse_url($serverUrl);
        if (isset($parsedUrl['path'])) {
            $basePath = rtrim($parsedUrl['path'], '/');
        }
    } elseif (isset($openapi['basePath'])) {
        $basePath = rtrim((string) $openapi['basePath'], '/');
    }
    $baseUrl = 'http://127.0.0.1:8080' . $basePath;
    $sdkTestCode .= "        \$this->client = new ApiClient('$baseUrl');
";
    $secSchemes = $openapi['components']['securitySchemes'] ?? ($openapi['securityDefinitions'] ?? []);
    if (is_array($secSchemes) && !empty($secSchemes)) {
        foreach ($secSchemes as $secName => $secDef) {
            $secType = $secDef['type'] ?? '';
            if ($secType === 'apiKey') {
                $sdkTestCode .= "        \$this->client->setApiKey(" . var_export((string) $secName, true) . ", 'special-key');
";
            } else {
                $sdkTestCode .= "        \$this->client->setBearerToken(" . var_export((string) $secName, true) . ", 'special-key');
";
            }
        }
    } else {
        $sdkTestCode .= "        \$this->client->setApiKey('api_key', 'special-key');
";
        $sdkTestCode .= "        \$this->client->setBearerToken('petstore_auth', 'special-key');
";
    }
    $sdkTestCode .= "        file_put_contents(sys_get_temp_dir() . '/dummy.txt', 'dummy content');
";
    $sdkTestCode .= "    }

";

    if (isset($openapi['paths'])) {
        foreach ($openapi['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers'], true)) {
                    continue;
                }
                if ($method === 'additionalOperations' && is_array($operation)) {
                    continue;
                }

                $methodName = strtolower((string) $method);
                $opId = $operation['operationId'] ?? "{$methodName}_" . preg_replace('/[^a-zA-Z0-9]/', '_', (string) $path);

                $sdkTestCode .= "    public function test_{$opId}(): void {
";

                $dummyParams = [];
                if (isset($operation['parameters'])) {
                    foreach ($operation['parameters'] as $p) {
                        if (!empty($p['required'])) {
                            $name = $p['name'];
                            $schema = is_array($p['schema'] ?? null) ? $p['schema'] : [];
                            $dummyParams[$name] = generateDummySchemaValue($schema, $openapi);
                        }
                    }
                }

                $dummyBody = [];
                if (isset($operation['requestBody']['content']['application/json']['schema'])) {
                    $res = generateDummySchemaValue($operation['requestBody']['content']['application/json']['schema'], $openapi);
                    $dummyBody = is_array($res) ? $res : ['dummy' => $res];
                } elseif (isset($operation['requestBody']['content']['application/x-www-form-urlencoded']['schema'])) {
                    $res = generateDummySchemaValue($operation['requestBody']['content']['application/x-www-form-urlencoded']['schema'], $openapi);
                    $dummyBody = is_array($res) ? $res : ['dummy' => $res];
                } elseif (isset($operation['requestBody']['content']['multipart/form-data']['schema'])) {
                    $res = generateDummySchemaValue($operation['requestBody']['content']['multipart/form-data']['schema'], $openapi);
                    $dummyBody = is_array($res) ? $res : ['dummy' => $res];
                    foreach ($operation['requestBody']['content']['multipart/form-data']['schema']['properties'] ?? [] as $prop => $propDef) {
                        if (($propDef['type'] ?? '') === 'file' || ($propDef['format'] ?? '') === 'binary') {
                            $dummyBody[$prop] = '___CURLFILE_PLACEHOLDER___';
                        }
                    }
                }

                $paramsStr = empty($dummyParams) ? '[]' : var_export($dummyParams, true);
                $bodyStr = empty($dummyBody) ? '[]' : var_export($dummyBody, true);
                $bodyStr = str_replace("'___CURLFILE_PLACEHOLDER___'", "new \\CURLFile(sys_get_temp_dir() . '/dummy.txt', '', 'dummy.txt')", $bodyStr);

                $sdkTestCode .= "        \$response = \$this->client->{$opId}($paramsStr, $bodyStr);
";
                $sdkTestCode .= "        \$this->assertTrue(is_numeric(\$response['status']) && \$response['status'] > 0, 'Did not receive a valid HTTP Status Code');
";
                $sdkTestCode .= "        if (\$response['status'] >= 200 && \$response['status'] < 300) {
";
                $sdkTestCode .= "            if (\$response['data'] === null && json_last_error() !== JSON_ERROR_NONE) {
";
                $sdkTestCode .= "                \$this->fail('Payload failed to deserialize');
";
                $sdkTestCode .= "            }
";
                $sdkTestCode .= "        }
";
                $sdkTestCode .= "    }

";
            }
        }
    }

    $sdkTestCode .= "}
";
    file_put_contents("$testsDir/SdkIntegrationTest.php", $sdkTestCode);
}

/**
 * Emits `composer.json` and `.github/workflows/ci.yml` scaffolding for generated projects.
 *
 * @param string $outDir Output project directory.
 * @param string $subcommand Target generation mode (`to_server`, `to_sdk`, etc.).
 * @param bool $noInstallablePackage Skip `composer.json` emission if true.
 * @param bool $noGithubActions Skip GitHub Actions workflow emission if true.
 * @return void
 */
function emitProjectScaffolding(string $outDir, string $subcommand, bool $noInstallablePackage, bool $noGithubActions): void
{
    if (!$noInstallablePackage) {
        if (!file_exists("$outDir/composer.json")) {
            $composerData = [
                'name' => 'offscale/generated-api',
                'description' => 'Generated API client/server',
                'require' => [
                    'php' => '>=8.0',
                ],
                'require-dev' => [
                    'phpunit/phpunit' => '^10.0',
                ],
                'scripts' => [
                    'test' => 'vendor/bin/phpunit tests',
                ],
                'autoload' => [
                    'psr-4' => [
                        'Api\\' => 'src/',
                    ],
                ],
            ];
            if ($subcommand === 'to_server') {
                $composerData['require']['illuminate/database'] = '^10.0 || ^11.0';
                $composerData['require']['illuminate/routing'] = '^10.0 || ^11.0';
                $composerData['require']['illuminate/events'] = '^10.0 || ^11.0';
                $composerData['require']['fakerphp/faker'] = '^1.24';
                $composerData['require']['ext-pdo'] = '*';
                $composerData['require']['ext-pdo_sqlite'] = '*';
            }
            file_put_contents("$outDir/composer.json", json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    if (!$noGithubActions) {
        if (!is_dir("$outDir/.github/workflows")) {
            mkdir("$outDir/.github/workflows", 0777, true);
        }
        if (!file_exists("$outDir/.github/workflows/ci.yml")) {
            file_put_contents("$outDir/.github/workflows/ci.yml", "name: CI
on: [push]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v6
    - name: Use PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    - run: composer install
    - run: composer test
");
        }
    }
}

/**
 * Downgrades an OpenAPI 3.x structure into a Swagger 2.0 specification array.
 *
 * @param array<string, mixed> $openapi The OpenAPI 3.x specification array.
 * @return array<string, mixed> The converted Swagger 2.0 specification array.
 */
function downgradeOpenApi3ToSwagger2(array $openapi): array
{
    $swagger = ['swagger' => '2.0'];
    if (isset($openapi['info'])) {
        $swagger['info'] = $openapi['info'];
    }
    if (isset($openapi['servers']) && count($openapi['servers']) > 0) {
        $url = parse_url((string) $openapi['servers'][0]['url']);
        if (isset($url['host'])) {
            $swagger['host'] = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
        }
        if (isset($url['path'])) {
            $swagger['basePath'] = $url['path'];
        }
        if (isset($url['scheme'])) {
            $swagger['schemes'] = [$url['scheme']];
        }
    }
    $swagger['consumes'] = ['application/json'];
    $swagger['produces'] = ['application/json'];
    if (isset($openapi['paths'])) {
        $swagger['paths'] = (array) $openapi['paths'];
        foreach ($swagger['paths'] as $path => &$pathItem) {
            foreach ($pathItem as $method => &$op) {
                if (in_array(strtolower((string) $method), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'], true)) {
                    if (isset($op['requestBody'])) {
                        $content = $op['requestBody']['content'] ?? [];
                        foreach ($content as $mime => $media) {
                            if ($mime === 'application/x-www-form-urlencoded' || $mime === 'multipart/form-data') {
                                $props = $media['schema']['properties'] ?? [];
                                if (!isset($op['parameters'])) {
                                    $op['parameters'] = [];
                                }
                                foreach ($props as $name => $propSchema) {
                                    $p = $propSchema;
                                    $p['name'] = $name;
                                    $p['in'] = 'formData';
                                    $op['parameters'][] = $p;
                                }
                            } else {
                                $p = [
                                    'in' => 'body',
                                    'name' => 'body',
                                    'required' => $op['requestBody']['required'] ?? false,
                                    'schema' => $media['schema'] ?? [],
                                ];
                                if (isset($op['requestBody']['description'])) {
                                    $p['description'] = $op['requestBody']['description'];
                                }
                                if (!isset($op['parameters'])) {
                                    $op['parameters'] = [];
                                }
                                $op['parameters'][] = $p;
                            }
                            break;
                        }
                        unset($op['requestBody']);
                    }
                    if (isset($op['responses'])) {
                        foreach ($op['responses'] as $code => &$resp) {
                            if (isset($resp['content'])) {
                                foreach ($resp['content'] as $mime => $media) {
                                    $resp['schema'] = $media['schema'] ?? [];
                                    break;
                                }
                                unset($resp['content']);
                            }
                        }
                    }
                }
            }
        }
    }
    if (isset($openapi['components']['schemas'])) {
        $swagger['definitions'] = $openapi['components']['schemas'];
    }
    if (isset($openapi['components']['parameters'])) {
        $swagger['parameters'] = $openapi['components']['parameters'];
    }
    if (isset($openapi['components']['responses'])) {
        $swagger['responses'] = $openapi['components']['responses'];
    }
    if (isset($openapi['components']['securitySchemes'])) {
        $swagger['securityDefinitions'] = $openapi['components']['securitySchemes'];
        foreach ($swagger['securityDefinitions'] as $name => &$scheme) {
            if ($scheme['type'] === 'http' && isset($scheme['scheme']) && $scheme['scheme'] === 'basic') {
                $scheme['type'] = 'basic';
                unset($scheme['scheme']);
            }
            if (isset($scheme['flows'])) {
                $scheme['type'] = 'oauth2';
                foreach ($scheme['flows'] as $flowType => $flow) {
                    $ft = $flowType;
                    if ($ft === 'clientCredentials') {
                        $ft = 'application';
                    }
                    if ($ft === 'authorizationCode') {
                        $ft = 'accessCode';
                    }
                    $scheme['flow'] = $ft;
                    if (isset($flow['authorizationUrl'])) {
                        $scheme['authorizationUrl'] = $flow['authorizationUrl'];
                    }
                    if (isset($flow['tokenUrl'])) {
                        $scheme['tokenUrl'] = $flow['tokenUrl'];
                    }
                    if (isset($flow['scopes'])) {
                        $scheme['scopes'] = $flow['scopes'];
                    }
                    break;
                }
                unset($scheme['flows']);
            }
        }
    }
    if (isset($openapi['security'])) {
        $swagger['security'] = $openapi['security'];
    }
    if (isset($openapi['tags'])) {
        $swagger['tags'] = $openapi['tags'];
    }
    if (isset($openapi['externalDocs'])) {
        $swagger['externalDocs'] = $openapi['externalDocs'];
    }

    return rewrite_ref_prefixes($swagger, [
        '#/components/schemas/' => '#/definitions/',
        '#/components/parameters/' => '#/parameters/',
        '#/components/responses/' => '#/responses/',
        '#/components/securitySchemes/' => '#/securityDefinitions/',
    ]);
}

/**
 * Emits an OpenAPI array structure as JSON string and coordinates generation
 * of the associated PHP code representations into the given directory.
 *
 * @param array<string, mixed> $openapi The parsed OpenAPI spec
 * @param string|null $outDir The directory to emit PHP code into
 * @param array<string, mixed> $options Generation options ('subcommand', 'tests', 'target_version', 'no_installable_package', 'no_github_actions', 'mcp')
 * @return string The JSON representation
 * @throws \RuntimeException If JSON encoding fails
 */
function emit(array $openapi, ?string $outDir = null, array $options = []): string
{
    if (!isset($openapi['openapi'])) {
        $openapi['openapi'] = '3.2.0';
    }

    if (!isset($openapi['info'])) {
        $openapi['info'] = [
            'title' => 'Default API',
            'version' => '0.0.3',
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
            if (file_exists(__DIR__ . '/mcp_schema.php')) {
                $mcpSchemas = include __DIR__ . '/mcp_schema.php';
                foreach ($mcpSchemas as $name => $schema) {
                    if (!isset($openapi['components']['schemas'][$name])) {
                        $openapi['components']['schemas'][$name] = $schema;
                    }
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
                $phpunitCode = "<?php\n\n// Auto-generated tests\n\nuse PHPUnit\\Framework\\TestCase;\n\nclass ApiTests extends TestCase {\n";
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
        throw new \RuntimeException('Failed to encode OpenAPI array to JSON: ' . json_last_error_msg());
    }

    return $json;
}
