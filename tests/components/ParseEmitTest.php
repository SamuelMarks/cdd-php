<?php

/**
 * Tests for OpenAPI Components Object parsing and emission (`\Cdd\Components`).
 *
 * @package Cdd\Tests\Components
 */

declare(strict_types=1);

namespace Cdd\Tests\Components;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Components\parse` and `\Cdd\Components\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting schemas, parameters, and responses in a Components Object.
     *
     * @return void
     */
    public function testParseAndEmit(): void
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

    /**
     * Tests emitting components while preserving existing code in `Models.php`.
     *
     * @return void
     */
    public function testEmitWithExistingCode(): void
    {
        $components = [
            'schemas' => [
                'NewUser' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
        $existing = "<?php

// Custom comment
class ExistingUser {}
";
        $emitted = \Cdd\Components\emit($components, $existing);
        $this->assertTrue(strpos($emitted, '// Custom comment') !== false);
        $this->assertTrue(strpos($emitted, 'class ExistingUser {}') !== false);
        $this->assertTrue(strpos($emitted, 'class NewUser') !== false);
        $this->assertTrue(strpos($emitted, "'id',") !== false);
    }

    /**
     * Tests emitting parameter, securityScheme, response, and mediaType component classes.
     *
     * @return void
     */
    public function testEmitOtherComponents(): void
    {
        $components = [
            'parameters' => [
                'LimitParam' => [
                    'name' => 'limit',
                    'in' => 'query',
                    'required' => false,
                    'schema' => ['type' => 'integer'],
                ],
            ],
            'securitySchemes' => [
                'BearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
            'mediaTypes' => [
                'JsonStream' => [
                    'description' => 'Streamed JSON items',
                    'itemSchema' => ['$ref' => '#/components/schemas/User'],
                    'itemEncoding' => ['contentType' => 'application/json'],
                ],
            ],
            'responses' => [
                'ErrorResponse' => [
                    'description' => 'A generic error response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => ['error' => ['type' => 'string']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $emitted = \Cdd\Components\emit($components);

        $this->assertTrue(strpos($emitted, '@parameter') !== false);
        $this->assertTrue(strpos($emitted, 'class LimitParam') !== false);

        $this->assertTrue(strpos($emitted, '@securityScheme') !== false);
        $this->assertTrue(strpos($emitted, '@type http') !== false);
        $this->assertTrue(strpos($emitted, 'class BearerAuth') !== false);

        $this->assertTrue(strpos($emitted, '@mediaType') !== false);
        $this->assertTrue(strpos($emitted, '@itemSchema User') !== false);
        $this->assertTrue(strpos($emitted, '@itemEncoding application/json') !== false);

        $this->assertTrue(strpos($emitted, '@response') !== false);
        $this->assertTrue(strpos($emitted, 'A generic error response') !== false);
        $this->assertTrue(strpos($emitted, 'class ErrorResponse') !== false);
        $this->assertTrue(strpos($emitted, "'error',") !== false);
    }

    /**
     * Tests emitting all security scheme types (`apiKey`, `http`, `openIdConnect`, `oauth2`).
     *
     * @return void
     */
    public function testEmitSecuritySchemesFull(): void
    {
        $components = [
            'parameters' => [
                'LimitParam' => [
                    'name' => 'limit',
                    'in' => 'query',
                    'required' => true,
                    'schema' => ['type' => 'integer'],
                ],
            ],
            'securitySchemes' => [
                'ApiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-KEY',
                ],
                'BearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
                'OpenIdAuth' => [
                    'type' => 'openIdConnect',
                    'openIdConnectUrl' => 'https://example.com/.well-known/openid-configuration',
                ],
                'OAuth2' => [
                    'type' => 'oauth2',
                    'flows' => [
                        'implicit' => [
                            'authorizationUrl' => 'https://example.com/api/oauth/dialog',
                            'scopes' => ['write:pets' => 'modify pets in your account'],
                        ],
                    ],
                ],
            ],
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
