<?php

/**
 * Tests for `\Cdd\Openapi\emit` generation options, scaffolding, and Swagger 2.0 downgrade.
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Openapi\emit` across `to_sdk`, `to_sdk_cli`, `to_server`, and `target_version=2.0`.
 */
class EmitOptionsTest extends TestCase
{
    /**
     * Tests default OpenAPI fields when emitting an empty specification array.
     *
     * @return void
     */
    public function testEmitDefaults(): void
    {
        $openapi = [];
        $emitted = \Cdd\Openapi\emit($openapi);
        $decoded = json_decode($emitted, true);

        $this->assertEquals('3.2.0', $decoded['openapi']);
        $this->assertEquals('Default API', $decoded['info']['title']);
        $this->assertEquals('0.0.4', $decoded['info']['version']);
        $this->assertTrue(is_array($decoded['paths']));
    }

    /**
     * Tests emitting an SDK project with tests and all parameter/requestBody media types.
     *
     * @return void
     */
    public function testEmitWithOutDirAndSubcommandToSdk(): void
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir_' . uniqid();

        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test API', 'version' => '1.0.0'],
            'servers' => [['url' => 'http://localhost']],
            'jsonSchemaDialect' => 'schema',
            'externalDocs' => ['url' => 'url'],
            'tags' => [['name' => 'tag']],
            'security' => [['api_key' => []]],
            'paths' => [
                '/test' => [
                    'summary' => 'a path summary',
                    'parameters' => [],
                    'get' => [
                        'operationId' => 'testGet',
                        'parameters' => [
                            ['name' => 'param1', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string']],
                            ['name' => 'param2', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
                            ['name' => 'param3', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'boolean']],
                            ['name' => 'param4', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'array', 'items' => ['type' => 'number']]],
                            ['name' => 'param5', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'object', 'properties' => ['foo' => ['type' => 'string']]]],
                            ['name' => 'param6', 'in' => 'query', 'required' => true, 'schema' => ['enum' => ['A', 'B']]],
                            ['name' => 'param7', 'in' => 'query', 'required' => true, 'schema' => ['$ref' => '#/components/schemas/Dummy']],
                            ['name' => 'param8', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'date-time']],
                            ['name' => 'param9', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'object']],
                        ],
                    ],
                    'post' => [
                        'operationId' => 'testPostJson',
                        'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'string']]]],
                    ],
                    'post_obj' => [
                        'operationId' => 'testPostObj',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'put' => [
                        'operationId' => 'testPutForm',
                        'requestBody' => [
                            'content' => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => ['type' => 'object', 'properties' => ['prop' => ['type' => 'string']]],
                                ],
                            ],
                        ],
                    ],
                    'patch' => [
                        'operationId' => 'testPatchMultipart',
                        'requestBody' => [
                            'content' => [
                                'multipart/form-data' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'prop' => ['type' => 'string'],
                                            'file' => ['type' => 'string', 'format' => 'binary'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Should be ignored in testing',
                        'parameters' => [],
                    ],
                    'additionalOperations' => ['should_be_ignored' => ['summary' => 'test']],
                    'post_json_obj' => [
                        'operationId' => 'testPostJsonObj',
                        'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['p' => ['type' => 'string']]]]]],
                    ],
                    'put_form_str' => [
                        'operationId' => 'testPutFormStr',
                        'requestBody' => ['content' => ['application/x-www-form-urlencoded' => ['schema' => ['type' => 'string']]]],
                    ],
                    'patch_multi_str' => [
                        'operationId' => 'testPatchMultiStr',
                        'requestBody' => ['content' => ['multipart/form-data' => ['schema' => ['type' => 'string']]]],
                    ],
                    'post_octet_bin' => [
                        'operationId' => 'testPostOctetBin',
                        'parameters' => [
                            ['name' => 'optionalMeta', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                        ],
                        'requestBody' => ['content' => ['application/octet-stream' => ['schema' => ['type' => 'string', 'format' => 'binary']]]],
                    ],
                    'post_octet_str' => [
                        'operationId' => 'testPostOctetStr',
                        'requestBody' => ['content' => ['application/octet-stream' => ['schema' => ['type' => 'string']]]],
                    ],
                    'post_octet_obj' => [
                        'operationId' => 'testPostOctetObj',
                        'requestBody' => ['content' => ['application/octet-stream' => ['schema' => ['type' => 'object', 'properties' => ['bin' => ['type' => 'string']]]]]],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Dummy' => ['type' => 'string'],
                ],
                'securitySchemes' => [
                    'api_key' => ['type' => 'apiKey', 'name' => 'api_key', 'in' => 'header'],
                    'petstore_auth' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
                'examples' => [
                    'Ex' => ['value' => 'test'],
                ],
            ],
            'webhooks' => [
                'myWebhook' => [
                    'post' => [
                        'operationId' => 'webhookPost',
                    ],
                ],
            ],
        ];

        $options = [
            'subcommand' => 'to_sdk',
            'tests' => true,
        ];

        \Cdd\Openapi\emit($openapi, $outDir, $options);

        $openapi['paths']['/test']['get'] = [
            'parameters' => [],
        ];
        $openapi['paths']['/test']['parameters'] = [];
        \Cdd\Openapi\emit($openapi, $outDir, $options);

        $this->assertTrue(is_dir($outDir . '/src'));
        $this->assertTrue(is_dir($outDir . '/tests'));
        $this->assertTrue(file_exists($outDir . '/src/ApiServers.php'));
        $this->assertTrue(file_exists($outDir . '/src/api_metadata.php'));
        $this->assertTrue(file_exists($outDir . '/src/ApiController.php'));
        $this->assertTrue(file_exists($outDir . '/src/routes.php'));
        $this->assertTrue(file_exists($outDir . '/src/ApiClient.php'));
        $this->assertTrue(file_exists($outDir . '/src/Models.php'));
        $this->assertTrue(file_exists($outDir . '/src/mocks.php'));
        $this->assertTrue(file_exists($outDir . '/src/Webhooks.php'));
        $this->assertTrue(file_exists($outDir . '/tests/ApiTests.php'));
        $this->assertTrue(file_exists($outDir . '/tests/ComposableTests.php'));
        $this->assertTrue(file_exists($outDir . '/tests/SdkIntegrationTest.php'));
        $this->assertTrue(file_exists($outDir . '/composer.json'));
        $this->assertTrue(file_exists($outDir . '/phpunit.xml'));
        $this->assertTrue(file_exists($outDir . '/.github/workflows/ci.yml'));

        $phpunitXml = (string) file_get_contents($outDir . '/phpunit.xml');
        $this->assertStringContainsString('<directory>tests</directory>', $phpunitXml);

        $composerJson = json_decode((string) file_get_contents($outDir . '/composer.json'), true);
        $this->assertTrue(isset($composerJson['require']));
        $this->assertEquals('src/', $composerJson['autoload']['psr-4']['Api\\']);
        $this->assertTrue(!isset($composerJson['require']['illuminate/database']));
        $this->assertTrue(!isset($composerJson['require']['fakerphp/faker']));
        $this->assertTrue(!file_exists($outDir . '/README.md'));
        $sdkTestContents = (string) file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertStringContainsString('use PHPUnit\\Framework\\TestCase;', $sdkTestContents);
        $this->assertStringContainsString('use Api\\ApiClient;', $sdkTestContents);
        $this->assertStringContainsString('new \\CURLFile(', $sdkTestContents);
        $this->assertStringContainsString('$this->assertLessThan(400, $response[\'status\']);', $sdkTestContents);
        $this->assertStringContainsString('$this->assertArrayNotHasKey(\'sabotage\', $response[\'data\']);', $sdkTestContents);

        $apiTestContents = (string) file_get_contents($outDir . '/tests/ApiTests.php');
        $this->assertStringContainsString('declare(strict_types=1);', $apiTestContents);
        $this->assertStringContainsString('protected function call(string $method, string $path, array $body = []): object', $apiTestContents);
        $adaptedApiTests = str_replace(
            ['use PHPUnit\\Framework\\TestCase;', 'class ApiTests extends TestCase'],
            ['use Cdd\\Tests\\Framework\\TestCase;', 'class GeneratedSdkApiTests extends TestCase'],
            $apiTestContents
        );
        $adaptedPath = $outDir . '/tests/GeneratedSdkApiTests.php';
        file_put_contents($adaptedPath, $adaptedApiTests);
        require_once $adaptedPath;
        /** @var object $apiTestObj */
        $apiTestObj = new \GeneratedSdkApiTests('testGetRoute');
        $apiTestObj->testGetRoute();
        $apiTestObj->testtestPostJson();

        $this->removeDir($outDir);
    }

    /**
     * Tests emitting with `no_installable_package` and `no_github_actions`.
     *
     * @return void
     */
    public function testEmitWithSubcommandToSdkCliAndNoPackages(): void
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir2_' . uniqid();

        $openapi = ['openapi' => '3.2.0', 'info' => ['title' => 'A', 'version' => '1'], 'paths' => []];
        $options = [
            'subcommand' => 'to_sdk_cli',
            'no_installable_package' => true,
            'no_github_actions' => true,
            'tests' => false,
        ];

        \Cdd\Openapi\emit($openapi, $outDir, $options);

        $this->assertTrue(!file_exists($outDir . '/tests/SdkIntegrationTest.php'));
        $this->assertTrue(!file_exists($outDir . '/composer.json'));
        $this->assertTrue(!file_exists($outDir . '/.github/workflows/ci.yml'));

        $this->removeDir($outDir);
    }

    /**
     * Tests emitting a full server project (`subcommand=to_server`).
     *
     * @return void
     */
    public function testEmitWithSubcommandToServer(): void
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir_server_' . uniqid();

        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'A', 'version' => '1'],
            'paths' => [
                '/users' => [
                    'get' => ['operationId' => 'listUsers', 'responses' => ['200' => ['description' => 'ok']]],
                ],
            ],
            'components' => [
                'schemas' => [
                    'User' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]],
                ],
                'examples' => [
                    'User' => ['dataValue' => ['id' => 1]],
                ],
            ],
        ];
        $options = [
            'subcommand' => 'to_server',
            'tests' => true,
        ];

        \Cdd\Openapi\emit($openapi, $outDir, $options);

        $this->assertTrue(is_dir($outDir . '/src/Controllers'));
        $this->assertTrue(file_exists($outDir . '/src/Controllers/Mcp_sseController.php'));
        $this->assertTrue(file_exists($outDir . '/src/Controllers/Mcp_messageController.php'));

        $this->assertTrue(file_exists($outDir . '/src/routes.php'));
        $this->assertTrue(file_exists($outDir . '/src/Routes/McpRoutes.php'));
        $mcpRouteCode = (string) file_get_contents($outDir . '/src/Routes/McpRoutes.php');
        $this->assertStringContainsString('/mcp/sse', $mcpRouteCode);
        $this->assertStringContainsString('/mcp/message', $mcpRouteCode);

        $this->assertTrue(file_exists($outDir . '/composer.json'));
        $composerJson = json_decode((string) file_get_contents($outDir . '/composer.json'), true);
        $this->assertTrue(isset($composerJson['require']));
        $this->assertEquals('src/', $composerJson['autoload']['psr-4']['Api\\']);
        $this->assertTrue(isset($composerJson['require']['illuminate/database']));
        $this->assertTrue(isset($composerJson['require']['fakerphp/faker']));
        $this->assertTrue(isset($composerJson['require']['ext-pdo']));
        $this->assertTrue(isset($composerJson['require']['ext-pdo_sqlite']));
        $this->assertStringContainsString('namespace Api\\Exceptions;', (string) file_get_contents($outDir . '/src/Exceptions/MockServerError.php'));
        $this->assertStringContainsString('\\Api\\Controllers\\Mcp_sseController::class', $mcpRouteCode);

        $this->assertTrue(file_exists($outDir . '/README.md'));
        $readmeContents = (string) file_get_contents($outDir . '/README.md');
        $this->assertStringContainsString('# A Server', $readmeContents);
        $this->assertStringContainsString('--ephemeral --seed', $readmeContents);

        $this->assertTrue(file_exists($outDir . '/server.php'));
        $this->assertStringContainsString('php -S 127.0.0.1:8080 server.php', (string) file_get_contents($outDir . '/server.php'));

        $this->removeDir($outDir);
    }

    /**
     * Tests downgrading OpenAPI 3.2.0 to Swagger 2.0 via `target_version=2.0`.
     *
     * @return void
     */
    public function testEmitTargetVersion20(): void
    {
        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test API', 'version' => '1.0.0'],
            'servers' => [['url' => 'https://api.example.com:8443/v1']],
            'paths' => [
                '/test' => [
                    'post' => [
                        'requestBody' => [
                            'required' => true,
                            'description' => 'Body description',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['type' => 'object'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'put' => [
                        'requestBody' => [
                            'content' => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'properties' => [
                                            'formField' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => ['Model' => ['type' => 'object']],
                'parameters' => ['Param' => ['name' => 'p', 'in' => 'query']],
                'responses' => ['Resp' => ['description' => 'R']],
                'securitySchemes' => [
                    'basicAuth' => ['type' => 'http', 'scheme' => 'basic'],
                    'oauth2_authCode' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'authorizationCode' => [
                                'authorizationUrl' => 'https://example.com/auth',
                                'tokenUrl' => 'https://example.com/token',
                            ],
                        ],
                    ],
                    'oauth2' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'clientCredentials' => [
                                'tokenUrl' => 'https://example.com/token',
                                'scopes' => ['read' => 'read scope'],
                            ],
                            'authorizationCode' => [
                                'authorizationUrl' => 'https://example.com/auth',
                                'tokenUrl' => 'https://example.com/token',
                            ],
                        ],
                    ],
                ],
            ],
            'security' => [['basicAuth' => ['type' => 'http', 'scheme' => 'basic'], 'oauth2_authCode' => []]],
            'tags' => [['name' => 'test']],
            'externalDocs' => ['url' => 'https://example.com/docs'],
        ];

        $options = ['target_version' => '2.0'];
        $emitted = \Cdd\Openapi\emit($openapi, null, $options);
        $decoded = json_decode($emitted, true);

        $this->assertEquals('2.0', $decoded['swagger']);
        $this->assertEquals('api.example.com:8443', $decoded['host']);
        $this->assertEquals('/v1', $decoded['basePath']);
        $this->assertEquals(['https'], $decoded['schemes']);
        $this->assertTrue(is_array($decoded['paths']['/test']['post']['parameters']));
        $this->assertEquals('body', $decoded['paths']['/test']['post']['parameters'][0]['name']);
        $this->assertEquals('formData', $decoded['paths']['/test']['put']['parameters'][0]['in']);
        $this->assertEquals('oauth2', $decoded['securityDefinitions']['oauth2']['type']);
        $this->assertEquals('application', $decoded['securityDefinitions']['oauth2']['flow']);
    }

    /**
     * Tests base URL path calculation for generated SDK integration tests.
     *
     * @return void
     */
    public function testEmitBaseUrlCalculation(): void
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir3_' . uniqid();

        $openapiNoPath = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test', 'version' => '1'],
            'servers' => [['url' => 'http://127.0.0.1:8080']],
            'paths' => [],
        ];
        \Cdd\Openapi\emit($openapiNoPath, $outDir, ['subcommand' => 'to_sdk', 'tests' => true]);
        $testCode = (string) file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertTrue(strpos($testCode, "new ApiClient('http://127.0.0.1:8080')") !== false);

        $openapiWithPath = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test', 'version' => '1'],
            'servers' => [['url' => 'http://127.0.0.1:8080/api/v2']],
            'paths' => [],
        ];
        \Cdd\Openapi\emit($openapiWithPath, $outDir, ['subcommand' => 'to_sdk', 'tests' => true]);
        $testCode = (string) file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertTrue(strpos($testCode, "new ApiClient('http://127.0.0.1:8080/api/v2')") !== false);

        $openapiBasePath = [
            'swagger' => '2.0',
            'info' => ['title' => 'Test', 'version' => '1'],
            'basePath' => '/api/v1',
            'paths' => [],
        ];
        \Cdd\Openapi\emit($openapiBasePath, $outDir, ['subcommand' => 'to_sdk', 'tests' => true]);
        $testCode = (string) file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertTrue(strpos($testCode, "new ApiClient('http://127.0.0.1:8080/api/v1')") !== false);

        $this->removeDir($outDir);
    }

    /**
     * Tests that generated `SdkIntegrationTest.php` and `ApiTests.php` pass `phpunit` against a mock HTTP server
     * and fail when the server returns HTTP 500 or sabotaged JSON payloads.
     *
     * @return void
     */
    public function testSdkIntegrationTestAndApiTestsPassPhpUnitAgainstMockServer(): void
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_sdk_phpunit_' . uniqid();
        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Petstore SDK Test', 'version' => '1.0.0'],
            'servers' => [['url' => 'http://127.0.0.1:8080/v2']],
            'paths' => [
                '/store/order/{orderId}' => [
                    'get' => [
                        'operationId' => 'getOrderById',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/pet/{petId}' => [
                    'parameters' => [
                        ['name' => 'petId', 'in' => 'path', 'schema' => ['type' => 'integer']],
                    ],
                    'get' => [
                        'operationId' => 'getPetById',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'post' => [
                        'operationId' => 'updatePetWithForm',
                        'parameters' => [
                            ['name' => 'name', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/pet/{petId}/uploadImage' => [
                    'post' => [
                        'operationId' => 'uploadFile',
                        'parameters' => [
                            ['name' => 'petId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'requestBody' => [
                            'content' => [
                                'application/octet-stream' => [
                                    'schema' => ['type' => 'string', 'format' => 'binary'],
                                ],
                            ],
                        ],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/user/login' => [
                    'get' => [
                        'operationId' => 'loginUser',
                        'parameters' => [
                            ['name' => 'username', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'password', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/user/{username}' => [
                    'get' => [
                        'operationId' => 'getUserByName',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'delete' => [
                        'operationId' => 'deleteUser',
                        'parameters' => [
                            ['name' => 'username', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                        ],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/tag/{slug}' => [
                    'get' => [
                        'operationId' => 'getTagBySlug',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/pet' => [
                    'post' => [
                        'operationId' => 'addPet',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'username' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['405' => ['description' => 'Invalid input']],
                    ],
                ],
            ],
        ];

        \Cdd\Openapi\emit($openapi, $outDir, ['subcommand' => 'to_sdk']);

        $this->assertTrue(file_exists("$outDir/phpunit.xml"));
        $this->assertTrue(file_exists("$outDir/tests/SdkIntegrationTest.php"));
        $this->assertTrue(file_exists("$outDir/tests/ApiTests.php"));

        $sdkTestCode = (string) file_get_contents("$outDir/tests/SdkIntegrationTest.php");
        $this->assertStringContainsString("['orderId' => 1]", $sdkTestCode);
        $this->assertStringContainsString("['petId' => 1]", $sdkTestCode);
        $this->assertStringContainsString("'status' => 'available'", $sdkTestCode);
        $this->assertStringContainsString("['username' => 'user1']", $sdkTestCode);
        $this->assertStringContainsString("['slug' => 'test_string']", $sdkTestCode);
        $this->assertStringContainsString("\$this->assertLessThan(400, \$response['status']);", $sdkTestCode);
        $this->assertStringContainsString("\$this->assertArrayNotHasKey('sabotage', \$response['data']);", $sdkTestCode);

        $rootVendorAutoload = realpath(dirname(__DIR__, 2) . '/vendor/autoload.php');
        $phpunitBin = realpath(dirname(__DIR__, 2) . '/vendor/bin/phpunit');
        mkdir("$outDir/vendor", 0777, true);
        file_put_contents(
            "$outDir/vendor/autoload.php",
            "<?php\nrequire_once " . var_export($rootVendorAutoload, true) . ";\nrequire_once __DIR__ . '/../src/ApiClient.php';\n"
        );

        $modeFile = "$outDir/mock_mode.txt";
        file_put_contents($modeFile, 'happy');
        $routerFile = "$outDir/mock_router.php";
        file_put_contents(
            $routerFile,
            "<?php\n\$mode = trim((string) @file_get_contents(" . var_export($modeFile, true) . "));\n"
            . "\$uri = (string) parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);\n"
            . "\$method = strtoupper((string) (\$_SERVER['REQUEST_METHOD'] ?? 'GET'));\n"
            . "\$ct = (string) (\$_SERVER['CONTENT_TYPE'] ?? (\$_SERVER['HTTP_CONTENT_TYPE'] ?? ''));\n"
            . "\$accept = (string) (\$_SERVER['HTTP_ACCEPT'] ?? '');\n"
            . "header('Content-Type: application/json');\n"
            . "if (\$mode === '500') { http_response_code(500); echo json_encode(['error' => 'sabotage']); exit; }\n"
            . "if (\$mode === 'sabotage') { http_response_code(200); echo json_encode(['id' => 'bad', 'sabotage' => true]); exit; }\n"
            . "if (\$accept !== 'application/json') { http_response_code(406); echo '<error>XML not expected</error>'; exit; }\n"
            . "if (\$method === 'POST' && preg_match('#/pet/[0-9]+$#', \$uri)) {\n"
            . "    if (!str_starts_with(\$ct, 'application/x-www-form-urlencoded')) { http_response_code(415); exit; }\n"
            . "    if ((\$statusParam = (string) (\$_GET['status'] ?? '')) !== '' && !in_array(\$statusParam, ['available', 'pending', 'sold'], true)) { http_response_code(400); exit; }\n"
            . "    http_response_code(200); echo ''; exit;\n"
            . "}\n"
            . "if (\$method === 'POST' && str_ends_with(\$uri, '/uploadImage')) {\n"
            . "    if (!str_starts_with(\$ct, 'multipart/form-data') || empty(\$_FILES['file'])) { http_response_code(415); exit; }\n"
            . "    http_response_code(200); echo json_encode(['code' => 200, 'type' => 'unknown', 'message' => 'uploaded']); exit;\n"
            . "}\n"
            . "if (str_ends_with(\$uri, '/user/login')) { header('Content-Type: text/plain'); http_response_code(200); echo 'logged in user session:12345'; exit; }\n"
            . "if (\$method === 'DELETE') { http_response_code(200); echo ''; exit; }\n"
            . "http_response_code(200); echo json_encode(['id' => 1, 'name' => 'doggie', 'status' => 'available']);\n"
        );

        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $sockName = is_resource($sock) ? (string) stream_socket_get_name($sock, false) : '127.0.0.1:18089';
        if (is_resource($sock)) {
            fclose($sock);
        }
        $parts = explode(':', $sockName);
        $port = (int) ($parts[1] ?? 18089);

        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open([$phpBin, '-S', "127.0.0.1:$port", $routerFile], $descriptors, $pipes, $outDir);
        $deadline = microtime(true) + 3.0;
        while (microtime(true) < $deadline) {
            $fp = @fsockopen('127.0.0.1', $port, $e1, $e2, 0.1);
            if (is_resource($fp)) {
                fclose($fp);
                break;
            }
            usleep(20000);
        }

        try {
            $baseUrl = "http://127.0.0.1:$port/v2";
            $cmdSdk = 'BASE_URL=' . escapeshellarg($baseUrl) . ' ' . escapeshellarg($phpBin) . ' ' . escapeshellarg((string) $phpunitBin) . ' --configuration ' . escapeshellarg("$outDir/phpunit.xml") . ' 2>&1';
            exec($cmdSdk, $outSdk, $codeSdk);
            $this->assertEquals(0, $codeSdk);

            $cmdApi = escapeshellarg($phpBin) . ' ' . escapeshellarg((string) $phpunitBin) . ' --configuration ' . escapeshellarg("$outDir/phpunit.xml") . ' ' . escapeshellarg("$outDir/tests/ApiTests.php") . ' 2>&1';
            exec($cmdApi, $outApi, $codeApi);
            $this->assertEquals(0, $codeApi);

            file_put_contents($modeFile, '500');
            exec($cmdSdk, $out500, $code500);
            $this->assertTrue($code500 !== 0);

            file_put_contents($modeFile, 'sabotage');
            exec($cmdSdk, $outSabotage, $codeSabotage);
            $this->assertTrue($codeSabotage !== 0);
        } finally {
            if (is_resource($proc)) {
                proc_terminate($proc);
                proc_close($proc);
            }
            $this->removeDir($outDir);
        }
    }

    /**
     * Tests that a generated `to_server` standalone server (`server.php`) serves requests from a generated `to_sdk`
     * project with 100% passing `SdkIntegrationTest` across POST, PUT, GET, and DELETE routes.
     *
     * @return void
     */
    public function testGeneratedServerAndSdkEndToEnd(): void
    {
        $serverDir = sys_get_temp_dir() . '/cdd_test_gen_srv_' . uniqid();
        $sdkDir = sys_get_temp_dir() . '/cdd_test_gen_sdk_' . uniqid();
        $openapi = [
            'openapi' => '3.0.4',
            'info' => ['title' => 'Petstore E2E', 'version' => '1.0.0'],
            'servers' => [['url' => 'http://127.0.0.1:8080/api/v3']],
            'paths' => [
                '/pet' => [
                    'put' => [
                        'operationId' => 'updatePet',
                        'security' => [['petstore_auth' => ['write:pets']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Pet'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Pet'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'operationId' => 'addPet',
                        'security' => [['petstore_auth' => ['write:pets']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Pet'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Pet'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/pet/findByStatus' => [
                    'get' => [
                        'operationId' => 'findPetsByStatus',
                        'parameters' => [
                            ['name' => 'status', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'enum' => ['available']]],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Pet']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/pet/{petId}' => [
                    'get' => [
                        'operationId' => 'getPetById',
                        'parameters' => [
                            ['name' => 'petId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Pet'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'delete' => [
                        'operationId' => 'deletePet',
                        'parameters' => [
                            ['name' => 'petId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Deleted'],
                        ],
                    ],
                ],
                '/user/{username}' => [
                    'get' => [
                        'operationId' => 'getUserByName',
                        'parameters' => [
                            ['name' => 'username', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            'default' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/User'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Category' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'Pet' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'category' => ['$ref' => '#/components/schemas/Category'],
                            'status' => ['type' => 'string'],
                        ],
                    ],
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'username' => ['type' => 'string'],
                        ],
                    ],
                ],
                'securitySchemes' => [
                    'petstore_auth' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'implicit' => [
                                'authorizationUrl' => 'https://example.com/oauth',
                                'scopes' => ['write:pets' => 'write'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        \Cdd\Openapi\emit($openapi, $serverDir, ['subcommand' => 'to_server']);
        \Cdd\Openapi\emit($openapi, $sdkDir, ['subcommand' => 'to_sdk']);

        $rootVendorAutoload = realpath(dirname(__DIR__, 2) . '/vendor/autoload.php');
        $phpunitBin = realpath(dirname(__DIR__, 2) . '/vendor/bin/phpunit');
        foreach ([$serverDir, $sdkDir] as $dir) {
            mkdir("$dir/vendor", 0777, true);
            file_put_contents(
                "$dir/vendor/autoload.php",
                "<?php\n\$l = require " . var_export($rootVendorAutoload, true) . ";\n\$l->addPsr4('Api\\\\', __DIR__ . '/../src/');\nreturn \$l;\n"
            );
        }

        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $sockName = is_resource($sock) ? (string) stream_socket_get_name($sock, false) : '127.0.0.1:18090';
        if (is_resource($sock)) {
            fclose($sock);
        }
        $parts = explode(':', $sockName);
        $port = (int) ($parts[1] ?? 18090);

        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $env = array_merge($_ENV, ['DATABASE_URL' => '']);
        $proc = proc_open([$phpBin, '-S', "127.0.0.1:$port", "$serverDir/server.php"], $descriptors, $pipes, $serverDir, $env);
        $deadline = microtime(true) + 3.0;
        while (microtime(true) < $deadline) {
            $fp = @fsockopen('127.0.0.1', $port, $e1, $e2, 0.1);
            if (is_resource($fp)) {
                fclose($fp);
                break;
            }
            usleep(20000);
        }

        try {
            $baseUrl = "http://127.0.0.1:$port/api/v3";
            $cmdSdk = 'DATABASE_URL="" BASE_URL=' . escapeshellarg($baseUrl) . ' ' . escapeshellarg($phpBin) . ' ' . escapeshellarg((string) $phpunitBin) . ' --configuration ' . escapeshellarg("$sdkDir/phpunit.xml") . ' 2>&1';
            exec($cmdSdk, $outSdk, $codeSdk);
            $this->assertEquals(0, $codeSdk);
        } finally {
            if (is_resource($proc)) {
                proc_terminate($proc);
                proc_close($proc);
            }
            $this->removeDir($serverDir);
            $this->removeDir($sdkDir);
        }
    }

    /**
     * Tests exception thrown when `json_encode` fails in `\Cdd\Openapi\emit`.
     *
     * @return void
     */
    public function testEmitJsonEncodeFailure(): void
    {
        $openapi = ['openapi' => '3.2.0', 'info' => ['title' => 'A', 'version' => '1'], 'paths' => []];
        $openapi['paths'] = &$openapi;

        $this->assertThrows(function () use (&$openapi): void {
            @\Cdd\Openapi\emit($openapi);
        }, \Cdd\Exceptions\CddException::class, 'Failed to encode OpenAPI array to JSON');
    }
}
