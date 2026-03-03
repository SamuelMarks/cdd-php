<?php

declare(strict_types=1);

namespace Cdd\Tests\Components;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
                'required' => ['id'],
            ],
        ];
        
        $components = \Cdd\Components\parse($schemas);
        $this->assertEquals(1, count($components['schemas']));
        
        $emitted = \Cdd\Components\emit($components);
        $this->assertTrue(strpos($emitted, 'class User extends \Illuminate\Database\Eloquent\Model {') !== false);
        $this->assertTrue(strpos($emitted, "'id',") !== false);
    }

    public function testEmitWithExistingCode() {
        $components = [
            'schemas' => [
                'NewUser' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ]
                ]
            ]
        ];
        $existing = "<?php\n\n// Custom comment\nclass ExistingUser {}\n";
        $emitted = \Cdd\Components\emit($components, $existing);
        $this->assertTrue(strpos($emitted, '// Custom comment') !== false);
        $this->assertTrue(strpos($emitted, 'class ExistingUser {}') !== false);
        $this->assertTrue(strpos($emitted, 'class NewUser') !== false);
        $this->assertTrue(strpos($emitted, "'id',") !== false);
    }

    public function testEmitOtherComponents() {
        $components = [
            'parameters' => [
                'LimitParam' => [
                    'name' => 'limit',
                    'in' => 'query',
                    'required' => false,
                    'schema' => ['type' => 'integer']
                ]
            ],
            'securitySchemes' => [
                'BearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer'
                ]
            ],
            'responses' => [
                'ErrorResponse' => [
                    'description' => 'A generic error response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => ['error' => ['type' => 'string']]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $emitted = \Cdd\Components\emit($components);
        
        $this->assertTrue(strpos($emitted, '@parameter') !== false);
        $this->assertTrue(strpos($emitted, '@in query') !== false);
        $this->assertTrue(strpos($emitted, 'class LimitParam') !== false);
        
        $this->assertTrue(strpos($emitted, '@securityScheme') !== false);
        $this->assertTrue(strpos($emitted, '@type http') !== false);
        $this->assertTrue(strpos($emitted, 'class BearerAuth') !== false);
        
        $this->assertTrue(strpos($emitted, '@response') !== false);
        $this->assertTrue(strpos($emitted, 'A generic error response') !== false);
        $this->assertTrue(strpos($emitted, 'class ErrorResponse') !== false);
        $this->assertTrue(strpos($emitted, "'error',") !== false);
    }
}
