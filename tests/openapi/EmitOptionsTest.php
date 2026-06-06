<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class EmitOptionsTest extends TestCase
{
    public function testEmitDefaults()
    {
        // Line 18-31: Ensuring basic fields are present when they are omitted
        $openapi = [];
        $emitted = \Cdd\Openapi\emit($openapi);
        $decoded = json_decode($emitted, true);

        $this->assertEquals('3.2.0', $decoded['openapi']);
        $this->assertEquals('Default API', $decoded['info']['title']);
        $this->assertEquals('0.0.1', $decoded['info']['version']);
        $this->assertTrue(is_array($decoded['paths'])); // wait, it's emitted as (object)[], which becomes empty object in JSON, so array in json_decode assoc=true
    }

    public function testEmitWithOutDirAndSubcommandToSdk()
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir_' . uniqid();

        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test API', 'version' => '1.0.0'],
            'servers' => [['url' => 'http://localhost']],
            'jsonSchemaDialect' => 'schema',
            'externalDocs' => ['url' => 'url'],
            'security' => [['basicAuth' => ['type' => 'http', 'scheme' => 'basic'],
                    'oauth2_authCode' => []]],
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
                        ]
                    ],
                    'post' => [
                        'operationId' => 'testPostJson',
                        'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'string']]]]
                    ],
                    'post_obj' => [
                        'operationId' => 'testPostObj',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ],
                    'put' => [
                        'operationId' => 'testPutForm',
                        'requestBody' => [
                            'content' => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => ['type' => 'object', 'properties' => ['prop' => ['type' => 'string']]]
                                ]
                            ]
                        ]
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
                                            'file' => ['type' => 'string', 'format' => 'binary']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'delete' => [
                        'summary' => 'Should be ignored in testing',
                        'parameters' => []
                    ],
                    'additionalOperations' => ['should_be_ignored' => ['summary' => 'test']],
                    'post_json_obj' => [
                        'operationId' => 'testPostJsonObj',
                        'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['p' => ['type' => 'string']]]]]]
                    ],
                    'put_form_str' => [
                        'operationId' => 'testPutFormStr',
                        'requestBody' => ['content' => ['application/x-www-form-urlencoded' => ['schema' => ['type' => 'string']]]]
                    ],
                    'patch_multi_str' => [
                        'operationId' => 'testPatchMultiStr',
                        'requestBody' => ['content' => ['multipart/form-data' => ['schema' => ['type' => 'string']]]]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'Dummy' => ['type' => 'string']
                ],
                'examples' => [
                    'Ex' => ['value' => 'test']
                ]
            ],
            'webhooks' => [
                'myWebhook' => [
                    'post' => [
                        'operationId' => 'webhookPost'
                    ]
                ]
            ]
        ];

        // test with to_sdk and tests => true
        $options = [
            'subcommand' => 'to_sdk',
            'tests' => true
        ];

        // Ensure functions exist to prevent fatal errors, mock them if needed
        \Cdd\Openapi\emit($openapi, $outDir, $options);

        // Run a SECOND time to hit the "file_exists" TRUE branches
        // Also add an operation without operationId to hit the preg_replace fallback
        $openapi['paths']['/test']['get'] = [
            'parameters' => []
        ];
        // Add a "parameters" method to hit the ignore branch
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
        $this->assertTrue(file_exists($outDir . '/src/ApiTests.php'));
        $this->assertTrue(file_exists($outDir . '/src/ComposableTests.php'));
        $this->assertTrue(file_exists($outDir . '/tests/SdkIntegrationTest.php'));
        $this->assertTrue(file_exists($outDir . '/composer.json'));
        $this->assertTrue(file_exists($outDir . '/.github/workflows/ci.yml'));

        // Cleanup
        system("rm -rf " . escapeshellarg($outDir));
    }

    public function testEmitWithSubcommandToSdkCliAndNoPackages()
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir2_' . uniqid();

        $openapi = ['openapi' => '3.2.0', 'info' => ['title' => 'A', 'version' => '1'], 'paths' => []];
        $options = [
            'subcommand' => 'to_sdk_cli',
            'no_installable_package' => true,
            'no_github_actions' => true,
            'tests' => false
        ];

        \Cdd\Openapi\emit($openapi, $outDir, $options);

        $this->assertTrue(!file_exists($outDir . '/tests/SdkIntegrationTest.php'));
        $this->assertTrue(!file_exists($outDir . '/composer.json'));
        $this->assertTrue(!file_exists($outDir . '/.github/workflows/ci.yml'));

        system("rm -rf " . escapeshellarg($outDir));
    }

    public function testEmitWithSubcommandToServer()
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir_server_' . uniqid();

        $openapi = ['openapi' => '3.2.0', 'info' => ['title' => 'A', 'version' => '1'], 'paths' => []];
        $options = [
            'subcommand' => 'to_server'
        ];

        \Cdd\Openapi\emit($openapi, $outDir, $options);

        // Check if /mcp/sse and /mcp/message routes were generated
        $this->assertTrue(file_exists($outDir . '/src/ApiController.php'));
        $controllerCode = file_get_contents($outDir . '/src/ApiController.php');
        $this->assertStringContainsString('function mcp_sse', $controllerCode);
        $this->assertStringContainsString('function mcp_message', $controllerCode);

        $this->assertTrue(file_exists($outDir . '/src/routes.php'));
        $routeCode = file_get_contents($outDir . '/src/routes.php');
        $this->assertStringContainsString('/mcp/sse', $routeCode);
        $this->assertStringContainsString('/mcp/message', $routeCode);

        system("rm -rf " . escapeshellarg($outDir));
    }

    public function testEmitTargetVersion20()
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
                                    'schema' => ['type' => 'object']
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'put' => [
                        'requestBody' => [
                            'content' => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'properties' => [
                                            'formField' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
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
                                'tokenUrl' => 'https://example.com/token'
                            ]
                        ]
                    ],
                    'basicAuth' => ['type' => 'http', 'scheme' => 'basic'],
                    'oauth2_authCode' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'authorizationCode' => [
                                'authorizationUrl' => 'https://example.com/auth',
                                'tokenUrl' => 'https://example.com/token'
                            ]
                        ]
                    ],
                    'oauth2' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'clientCredentials' => [
                                'tokenUrl' => 'https://example.com/token',
                                'scopes' => ['read' => 'read scope']
                            ],
                            'authorizationCode' => [
                                'authorizationUrl' => 'https://example.com/auth',
                                'tokenUrl' => 'https://example.com/token'
                            ]
                        ]
                    ]
                ]
            ],
            'security' => [['basicAuth' => ['type' => 'http', 'scheme' => 'basic'],
                    'oauth2_authCode' => []]],
            'tags' => [['name' => 'test']],
            'externalDocs' => ['url' => 'https://example.com/docs']
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
        $this->assertEquals('application', $decoded['securityDefinitions']['oauth2']['flow']); // first flow is clientCredentials mapped to application
    }

    public function testEmitBaseUrlCalculation()
    {
        $outDir = sys_get_temp_dir() . '/cdd_test_emit_outdir3_' . uniqid();

        // Test with URL without a path
        $openapiNoPath = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test', 'version' => '1'],
            'servers' => [['url' => 'http://localhost:8080']], // No path here
            'paths' => []
        ];
        \Cdd\Openapi\emit($openapiNoPath, $outDir, ['subcommand' => 'to_sdk', 'tests' => true]);
        $testCode = file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertTrue(strpos($testCode, "new ApiClient('http://localhost:8080')") !== false);

        // Test with URL with a path
        $openapiWithPath = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Test', 'version' => '1'],
            'servers' => [['url' => 'http://localhost:8080/api/v2']],
            'paths' => []
        ];
        \Cdd\Openapi\emit($openapiWithPath, $outDir, ['subcommand' => 'to_sdk', 'tests' => true]);
        $testCode = file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertTrue(strpos($testCode, "new ApiClient('http://localhost:8080/api/v2')") !== false);

        // Test with Swagger 2.0 basePath
        $openapiBasePath = [
            'swagger' => '2.0',
            'info' => ['title' => 'Test', 'version' => '1'],
            'basePath' => '/api/v1',
            'paths' => []
        ];
        \Cdd\Openapi\emit($openapiBasePath, $outDir, ['subcommand' => 'to_sdk', 'tests' => true]);
        $testCode = file_get_contents($outDir . '/tests/SdkIntegrationTest.php');
        $this->assertTrue(strpos($testCode, "new ApiClient('http://localhost:8080/api/v1')") !== false);

        system("rm -rf " . escapeshellarg($outDir));
    }

    public function testEmitJsonEncodeFailure()
    {
        // Force json_encode to fail by providing a circular reference
        $openapi = ['openapi' => '3.2.0', 'info' => ['title' => 'A', 'version' => '1'], 'paths' => []];
        $openapi['paths'] = &$openapi; // circular reference

        try {
            @\Cdd\Openapi\emit($openapi);
            $this->assertTrue(false); // Should not reach here
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Failed to encode OpenAPI array to JSON') !== false);
        }
    }
}
