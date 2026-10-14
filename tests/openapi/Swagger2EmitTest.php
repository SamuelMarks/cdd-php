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
}
