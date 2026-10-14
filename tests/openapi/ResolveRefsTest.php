<?php

/**
 * Tests for local `$ref` resolution and circular reference detection.
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `resolve_refs` in the `Cdd\Openapi` namespace.
 */
class ResolveRefsTest extends TestCase
{
    /**
     * Tests resolving valid local `$ref` pointers.
     *
     * @return void
     */
    public function testResolveRefs(): void
    {
        $components = [
            'schemas' => [
                'User' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];

        $structure = [
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/User',
                        ],
                    ],
                ],
            ],
        ];

        $resolved = resolve_refs($structure, $components);

        $this->assertEquals('object', $resolved['requestBody']['content']['application/json']['schema']['type']);
        $this->assertEquals('integer', $resolved['requestBody']['content']['application/json']['schema']['properties']['id']['type']);
        $this->assertTrue(!isset($resolved['requestBody']['content']['application/json']['schema']['$ref']));
    }

    /**
     * Tests unresolved and circular `$ref` pointers.
     *
     * @return void
     */
    public function testResolveRefsNotFound(): void
    {
        $components = [
            'schemas' => [],
        ];

        $structure = [
            'schema' => [
                '$ref' => '#/components/schemas/NonExistent',
            ],
        ];

        $resolved = resolve_refs($structure, $components);

        $this->assertEquals('#/components/schemas/NonExistent', $resolved['schema']['$ref']);

        $circularComponents = [
            'schemas' => [
                'Node' => [
                    'type' => 'object',
                    'properties' => [
                        'parent' => ['$ref' => '#/components/schemas/Node'],
                    ],
                ],
            ],
        ];
        $circularResolved = resolve_refs(['$ref' => '#/components/schemas/Node'], $circularComponents);
        $this->assertEquals('object', $circularResolved['type']);
        $this->assertEquals('#/components/schemas/Node', $circularResolved['properties']['parent']['$ref']);
    }
}
