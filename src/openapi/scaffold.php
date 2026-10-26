<?php

/**
 * Scaffolds generated PHP server, SDK, and CLI project structures from OpenAPI specifications.
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
    $excDir = "$srcDir/Exceptions";
    if (!is_dir($excDir)) {
        mkdir($excDir, 0777, true);
    }
    $daosCodeArray = \Cdd\Daos\emit_modular($openapi['components']['schemas'] ?? []);
    foreach ($daosCodeArray as $filename => $code) {
        if ($filename === 'MockServerError.php') {
            file_put_contents("$excDir/$filename", $code);
        } else {
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
                $propArr = is_array($propDef) ? $propDef : [];
                if ($propName === 'username' && ($propArr['type'] ?? 'string') === 'string') {
                    $obj[$propName] = 'user1';
                } else {
                    $obj[$propName] = generateDummySchemaValue($propArr, $openapi);
                }
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
 * Exports a PHP value using short array syntax (`[...]`).
 *
 * @param mixed $val The PHP value to export.
 * @return string The formatted PHP literal expression.
 */
function exportPhpShortArray(mixed $val): string
{
    if (!is_array($val)) {
        return var_export($val, true);
    }
    if (empty($val)) {
        return '[]';
    }
    $isList = array_is_list($val);
    $parts = [];
    foreach ($val as $k => $v) {
        $exportedVal = exportPhpShortArray($v);
        $parts[] = $isList ? $exportedVal : var_export($k, true) . ' => ' . $exportedVal;
    }
    return '[' . implode(', ', $parts) . ']';
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
    $sdkTestCode = "<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Api\ApiClient;

class SdkIntegrationTest extends TestCase {
";
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
    $sdkTestCode .= "        \$baseUrl = getenv('BASE_URL') ?: '$baseUrl';
";
    $sdkTestCode .= "        \$this->client = new ApiClient('$baseUrl');
";
    $sdkTestCode .= "        if (\$baseUrl !== '$baseUrl') {
";
    $sdkTestCode .= "            \$this->client = new ApiClient((string) \$baseUrl);
";
    $sdkTestCode .= "        }
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
        $writeOps = [];
        $readOps = [];
        $deleteOps = [];
        foreach ($openapi['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array(strtolower((string) $method), ['parameters', 'summary', 'description', 'servers'], true)) {
                    continue;
                }
                if ($method === 'additionalOperations' && is_array($operation)) {
                    continue;
                }
                $entry = ['path' => (string) $path, 'methods' => $methods, 'method' => (string) $method, 'operation' => $operation];
                $mLower = strtolower((string) $method);
                if ($mLower === 'delete') {
                    $deleteOps[] = $entry;
                } elseif (in_array($mLower, ['post', 'put', 'patch'], true)) {
                    $writeOps[] = $entry;
                } else {
                    $readOps[] = $entry;
                }
            }
        }
        foreach (array_merge($writeOps, $readOps, $deleteOps) as $item) {
            $path = $item['path'];
            $methods = $item['methods'];
            $method = $item['method'];
            $operation = $item['operation'];
            {
                $methodName = strtolower((string) $method);
                $rawOpId = (string) ($operation['operationId'] ?? "{$methodName}_" . preg_replace('/[^a-zA-Z0-9]/', '_', (string) $path));
                $opId = \Cdd\Parameters\sanitize_var_name($rawOpId);

                $sdkTestCode .= "    public function test_{$opId}(): void {
";

                $dummyParams = [];
                $allParams = array_merge(
                    is_array($methods['parameters'] ?? null) ? $methods['parameters'] : [],
                    is_array($operation['parameters'] ?? null) ? $operation['parameters'] : []
                );
                foreach ($allParams as $p) {
                    if (!empty($p['required']) || ($p['in'] ?? '') === 'path') {
                        $name = (string) $p['name'];
                        $schema = is_array($p['schema'] ?? null) ? $p['schema'] : $p;
                        if ($name === 'username' && ($schema['type'] ?? 'string') === 'string') {
                            $dummyParams[$name] = 'user1';
                        } else {
                            $dummyParams[$name] = generateDummySchemaValue($schema, $openapi);
                        }
                    }
                }
                if (preg_match_all('/\{([^}]+)\}/', (string) $path, $pathMatches)) {
                    foreach ($pathMatches[1] as $pathParamName) {
                        if (!array_key_exists($pathParamName, $dummyParams)) {
                            $dummyParams[$pathParamName] = $pathParamName === 'username'
                                ? 'user1'
                                : (str_ends_with(strtolower($pathParamName), 'id') ? 1 : 'test_string');
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

                $paramsStr = exportPhpShortArray($dummyParams);
                $bodyStr = exportPhpShortArray($dummyBody);
                $bodyStr = str_replace("'___CURLFILE_PLACEHOLDER___'", "new \CURLFile(sys_get_temp_dir() . '/dummy.txt', '', 'dummy.txt')", $bodyStr);

                $sdkTestCode .= "        \$response = \$this->client->{$opId}($paramsStr, $bodyStr);
";
                $sdkTestCode .= "        \$this->assertTrue(is_numeric(\$response['status']) && \$response['status'] > 0, 'Did not receive a valid HTTP Status Code');
";
                $sdkTestCode .= "        \$this->assertLessThan(400, \$response['status']);
";
                $sdkTestCode .= "        if (is_array(\$response['data'])) {
";
                $sdkTestCode .= "            \$this->assertArrayNotHasKey('sabotage', \$response['data']);
";
                $sdkTestCode .= "        }
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
 * Emits `composer.json`, `phpunit.xml`, and `.github/workflows/ci.yml` scaffolding for generated projects.
 *
 * @param string $outDir Output project directory.
 * @param string $subcommand Target generation mode (`to_server`, `to_sdk`, etc.).
 * @param bool $noInstallablePackage Skip `composer.json` and `phpunit.xml` emission if true.
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
                        'Api' . chr(92) => 'src/',
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
        if (!file_exists("$outDir/phpunit.xml")) {
            file_put_contents("$outDir/phpunit.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<phpunit bootstrap=\"vendor/autoload.php\" colors=\"true\">
    <testsuites>
        <testsuite name=\"Test Suite\">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
");
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
