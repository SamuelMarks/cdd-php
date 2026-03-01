<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ResolveRefsTest extends TestCase {
    public function testResolveRefs() {
        $components = [
            'schemas' => [
                'User' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer']
                    ]
                ]
            ]
        ];
        
        $structure = [
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/User'
                        ]
                    ]
                ]
            ]
        ];
        
        $resolved = \Cdd\Openapi\resolve_refs($structure, $components);
        
        $this->assertEquals('object', $resolved['requestBody']['content']['application/json']['schema']['type']);
        $this->assertEquals('integer', $resolved['requestBody']['content']['application/json']['schema']['properties']['id']['type']);
        $this->assertTrue(!isset($resolved['requestBody']['content']['application/json']['schema']['$ref']));
    }
}
