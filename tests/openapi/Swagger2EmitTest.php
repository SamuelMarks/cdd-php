<?php

/**
 * Tests for Swagger 2.0 emission and `$ref` prefix rewriting.
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Openapi\emit` with `target_version = '2.0'`.
 */
class Swagger2EmitTest extends TestCase
{
    /**
     * Tests downgrading an OpenAPI 3.2.0 specification to Swagger 2.0.
     *
     * @return void
     */
    public function testEmitSwagger2(): void
    {
        $openapi = [
            'openapi' => '3.2.0',
            'info' => [
                'title' => 'Test',
                'version' => '1.0',
            ],
            'servers' => [
                ['url' => 'https://api.example.com/v2'],
            ],
            'paths' => [
                '/test' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Input',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/Output',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Input' => ['type' => 'string'],
                    'Output' => ['type' => 'string'],
                ],
            ],
        ];

        $json = \Cdd\Openapi\emit($openapi, null, ['target_version' => '2.0']);
        $decoded = json_decode($json, true);

        $this->assertEquals('2.0', $decoded['swagger']);
        $this->assertEquals('api.example.com', $decoded['host']);
        $this->assertEquals('/v2', $decoded['basePath']);
        $this->assertEquals(['https'], $decoded['schemes']);
        $this->assertEquals(['application/json'], $decoded['consumes']);
        $this->assertEquals(['application/json'], $decoded['produces']);

        $this->assertTrue(isset($decoded['paths']['/test']['post']['parameters'][0]));
        $this->assertEquals('body', $decoded['paths']['/test']['post']['parameters'][0]['in']);
        $this->assertEquals('#/definitions/Input', $decoded['paths']['/test']['post']['parameters'][0]['schema']['$ref']);

        $this->assertTrue(isset($decoded['paths']['/test']['post']['responses']['200']['schema']['$ref']));
        $this->assertEquals('#/definitions/Output', $decoded['paths']['/test']['post']['responses']['200']['schema']['$ref']);

        $this->assertTrue(isset($decoded['definitions']['Input']));
    }

    /**
     * Tests downgrading OpenAPI 3.2.0 parameters, items, and headers with `collectionFormat` / `style` to Swagger 2.0
     * and roundtripping through Server, Client SDK, and Client SDK CLI code generation and parsing.
     *
     * @return void
     */
    public function testEmitSwagger2CollectionFormatsAndRoundtrip(): void
    {
        $openapi = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'CollectionFormat API', 'version' => '1.0.0'],
            'paths' => [
                '/filter' => [
                    'parameters' => [
                        [
                            'name' => 'pathQuery',
                            'in' => 'query',
                            'style' => 'spaceDelimited',
                            'explode' => false,
                            'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                    'post' => [
                        'operationId' => 'filterRecords',
                        'parameters' => [
                            [
                                'name' => 'tags',
                                'in' => 'query',
                                'required' => true,
                                'style' => 'form',
                                'explode' => false,
                                'x-collectionFormat' => 'csv',
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'array',
                                        'collectionFormat' => 'pipes',
                                        'items' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'multiIds',
                                'in' => 'query',
                                'style' => 'form',
                                'explode' => true,
                                'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            [
                                'name' => 'pipeFilter',
                                'in' => 'query',
                                'style' => 'pipeDelimited',
                                'explode' => false,
                                'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            [
                                'name' => 'defaultArray',
                                'in' => 'query',
                                'style' => 'simple',
                                'explode' => false,
                                'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                        ],
                        'requestBody' => [
                            'content' => [
                                'application/x-www-form-urlencoded' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'formItems' => [
                                                'type' => 'array',
                                                'x-collectionFormat' => 'tsv',
                                                'items' => [
                                                    'type' => 'array',
                                                    'collectionFormat' => 'csv',
                                                    'items' => ['type' => 'string'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Filtered',
                                'headers' => [
                                    'X-Matrix-Header' => [
                                        'style' => 'simple',
                                        'explode' => false,
                                        'x-collectionFormat' => 'ssv',
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'array',
                                                'collectionFormat' => 'csv',
                                                'items' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'parameters' => [
                    'CompParam' => [
                        'name' => 'compParam',
                        'in' => 'query',
                        'style' => 'pipeDelimited',
                        'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
                'responses' => [
                    'CompResp' => [
                        'description' => 'Component response',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'headers' => [
                            'X-Comp-Header' => [
                                'style' => 'simple',
                                'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $json = \Cdd\Openapi\emit($openapi, null, ['target_version' => '2.0']);
        $swagger = json_decode($json, true);

        $this->assertEquals('ssv', $swagger['paths']['/filter']['parameters'][0]['collectionFormat']);
        $postParams = $swagger['paths']['/filter']['post']['parameters'];
        $this->assertEquals('csv', $postParams[0]['collectionFormat']);
        $this->assertEquals('pipes', $postParams[0]['items']['collectionFormat']);
        $this->assertEquals('multi', $postParams[1]['collectionFormat']);
        $this->assertEquals('pipes', $postParams[2]['collectionFormat']);
        $this->assertEquals('csv', $postParams[3]['collectionFormat']);
        $this->assertEquals('tsv', $postParams[4]['collectionFormat']);
        $this->assertEquals('csv', $postParams[4]['items']['collectionFormat']);

        $respHeader = $swagger['paths']['/filter']['post']['responses']['200']['headers']['X-Matrix-Header'];
        $this->assertEquals('ssv', $respHeader['collectionFormat']);
        $this->assertEquals('csv', $respHeader['items']['collectionFormat']);

        $this->assertEquals('pipes', $swagger['parameters']['CompParam']['collectionFormat']);
        $this->assertEquals('csv', $swagger['responses']['CompResp']['headers']['X-Comp-Header']['collectionFormat']);

        // Roundtrip through Server Controller (Operations\emit -> Controllers\parse -> downgrade)
        $opSpec = $openapi['paths']['/filter']['post'];
        $ctrlCode = "<?php\nclass FilterController {\n" . \Cdd\Operations\emit($opSpec, '/filter', 'post') . "\n}\n";
        $parsedCtrl = \Cdd\Controllers\parse($ctrlCode);
        $this->assertEquals('csv', $parsedCtrl['filterRecords']['parameters'][0]['x-collectionFormat']);
        $this->assertEquals('pipes', $parsedCtrl['filterRecords']['parameters'][0]['schema']['items']['collectionFormat']);
        $this->assertEquals('ssv', $parsedCtrl['filterRecords']['responses']['200']['headers']['X-Matrix-Header']['x-collectionFormat']);

        // Roundtrip through Client SDK (Client\emit_class -> Client\parse)
        $clientCode = \Cdd\Client\emit_class($openapi['paths']);
        $parsedClientPaths = \Cdd\Client\parse($clientCode);
        $this->assertEquals('csv', $parsedClientPaths['/filter']['post']['parameters'][0]['x-collectionFormat']);
        $this->assertEquals('pipes', $parsedClientPaths['/filter']['post']['parameters'][0]['schema']['items']['collectionFormat']);
        $this->assertEquals('ssv', $parsedClientPaths['/filter']['post']['responses']['200']['headers']['X-Matrix-Header']['x-collectionFormat']);

        // Roundtrip through Client SDK CLI (Cli\emit -> Cli\parse)
        $cliCode = \Cdd\Cli\emit($openapi['paths']);
        $parsedCliPaths = \Cdd\Cli\parse($cliCode);
        $this->assertEquals('csv', $parsedCliPaths['/cli/filter_records']['post']['parameters'][0]['x-collectionFormat']);
        $this->assertEquals('pipes', $parsedCliPaths['/cli/filter_records']['post']['parameters'][0]['schema']['items']['collectionFormat']);
        $this->assertEquals('ssv', $parsedCliPaths['/cli/filter_records']['post']['responses']['200']['headers']['X-Matrix-Header']['x-collectionFormat']);

        // Test operation servers -> schemes, response examples, formData required, and root x-* downgrade
        $openapiExtra = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Extra', 'version' => '1.0.0'],
            'x-custom-root' => 'preserved',
            'paths' => [
                '/upload' => [
                    'post' => [
                        'servers' => [['url' => 'https://upload.example.com/v1']],
                        'requestBody' => [
                            'content' => [
                                'multipart/form-data' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['doc'],
                                        'properties' => [
                                            'doc' => ['type' => 'file'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Uploaded',
                                'content' => [
                                    'application/xml' => [
                                        'schema' => ['type' => 'string'],
                                        'example' => '<ok/>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'responses' => [
                    'XmlResp' => [
                        'description' => 'XML response',
                        'content' => [
                            'application/xml' => [
                                'schema' => ['type' => 'string'],
                                'example' => '<resp/>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $swaggerExtra = json_decode(\Cdd\Openapi\emit($openapiExtra, null, ['target_version' => '2.0']), true);
        $this->assertEquals('preserved', $swaggerExtra['x-custom-root']);
        $this->assertEquals(['https'], $swaggerExtra['paths']['/upload']['post']['schemes']);
        $this->assertEquals(['multipart/form-data'], $swaggerExtra['paths']['/upload']['post']['consumes']);
        $this->assertEquals(['application/xml'], $swaggerExtra['paths']['/upload']['post']['produces']);
        $this->assertTrue($swaggerExtra['paths']['/upload']['post']['parameters'][0]['required']);
        $this->assertEquals('<ok/>', $swaggerExtra['paths']['/upload']['post']['responses']['200']['examples']['application/xml']);
        $this->assertEquals('<resp/>', $swaggerExtra['responses']['XmlResp']['examples']['application/xml']);
    }
}
