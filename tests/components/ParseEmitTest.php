<?php

declare(strict_types=1);

namespace Cdd\Tests\Components;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
                'required' => ['id'],
            ],
        ];

        $components = \Cdd\Components\parse($schemas, ['Param' => []], ['Resp' => []]);
        $this->assertEquals(1, count($components['schemas']));
        $this->assertEquals(1, count($components['parameters']));
        $this->assertEquals(1, count($components['responses']));

        $emitted = \Cdd\Components\emit($components);
        $this->assertTrue(strpos($emitted, 'class User extends \Illuminate\Database\Eloquent\Model {') !== false);
        $this->assertTrue(strpos($emitted, "'id',") !== false);
    }

    public function testEmitWithExistingCode()
    {
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

    public function testEmitOtherComponents()
    {
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
        $this->assertTrue(strpos($emitted, 'class LimitParam') !== false);

        $this->assertTrue(strpos($emitted, '@securityScheme') !== false);
        $this->assertTrue(strpos($emitted, '@type http') !== false);
        $this->assertTrue(strpos($emitted, 'class BearerAuth') !== false);

        $this->assertTrue(strpos($emitted, '@response') !== false);
        $this->assertTrue(strpos($emitted, 'A generic error response') !== false);
        $this->assertTrue(strpos($emitted, 'class ErrorResponse') !== false);
        $this->assertTrue(strpos($emitted, "'error',") !== false);
    }

    public function testEmitSecuritySchemesFull()
    {
        $components = [
            'parameters' => [
                'LimitParam' => [
                    'name' => 'limit',
                    'in' => 'query',
                    'required' => true,
                    'schema' => ['type' => 'integer']
                ]
            ],
            'securitySchemes' => [
                'ApiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-KEY'
                ],
                'BearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT'
                ],
                'OpenIdAuth' => [
                    'type' => 'openIdConnect',
                    'openIdConnectUrl' => 'https://example.com/.well-known/openid-configuration'
                ],
                'OAuth2' => [
                    'type' => 'oauth2',
                    'flows' => [
                        'implicit' => [
                            'authorizationUrl' => 'https://example.com/api/oauth/dialog',
                            'scopes' => ['write:pets' => 'modify pets in your account']
                        ]
                    ]
                ]
            ]
        ];

        $emitted = \Cdd\Components\emit($components);

        $this->assertTrue(strpos($emitted, '@required true') !== false);

        $this->assertTrue(strpos($emitted, '@in header') !== false);
        $this->assertTrue(strpos($emitted, '@name X-API-KEY') !== false);

        $this->assertTrue(strpos($emitted, '@bearerFormat JWT') !== false);

        $this->assertTrue(strpos($emitted, '@openIdConnectUrl https://example.com/.well-known/openid-configuration') !== false);

        $this->assertTrue(strpos($emitted, '@flow implicit') !== false);
    }
}
