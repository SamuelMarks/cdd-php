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
        $this->assertEquals('0.0.3', $decoded['info']['version']);
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
        $this->assertTrue(file_exists($outDir . '/.github/workflows/ci.yml'));

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
        }, \RuntimeException::class, 'Failed to encode OpenAPI array to JSON');
    }
}
