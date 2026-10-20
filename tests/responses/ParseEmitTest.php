<?php

/**
 * Tests for OpenAPI Response, Header, and Link Object parsing, emission, and validation (`\Cdd\Responses`).
 *
 * @package Cdd\Tests\Responses
 */

declare(strict_types=1);

namespace Cdd\Tests\Responses;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Responses\parse`, `\Cdd\Responses\emit`, and response/header/link validators.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting an OpenAPI Responses Object.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $res = \Cdd\Responses\parse('201', 'User', 'Created user');

        $this->assertEquals('Created user', $res['201']['description']);
        $this->assertEquals('#/components/schemas/User', $res['201']['content']['application/json']['schema']['$ref']);

        $emitted = \Cdd\Responses\emit($res);
        $this->assertEquals(" * @return User
", $emitted);

        $emittedWithMeta = \Cdd\Responses\emit($res, true);
        $this->assertEquals(" * @return User 201 Created user
", $emittedWithMeta);
        $this->assertEquals($res, \Cdd\Responses\parse($emittedWithMeta));
        $this->assertEquals(['200' => ['description' => 'Success']], \Cdd\Responses\parse(" * @return mixed
"));
    }

    /**
     * Tests emitting `@return` docblocks for scalar and fallback response types.
     *
     * @return void
     */
    public function testEmitTypes(): void
    {
        $res = \Cdd\Responses\parse('200', 'int', 'int val');
        $this->assertEquals(" * @return int
", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'float', 'float val');
        $this->assertEquals(" * @return float
", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'bool', 'bool val');
        $this->assertEquals(" * @return bool
", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'array', 'array val');
        $this->assertEquals(" * @return array
", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'object', 'obj val');
        $this->assertEquals(" * @return object
", \Cdd\Responses\emit($res));

        $res = [
            '200' => [
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'unknown_type'],
                    ],
                ],
            ],
        ];
        $this->assertEquals(" * @return mixed
", \Cdd\Responses\emit($res));

        $res = [
            '200' => [
                'content' => [
                    'application/json' => [],
                ],
            ],
        ];
        $this->assertEquals(" * @return mixed
", \Cdd\Responses\emit($res));
    }

    /**
     * Tests validating valid Response or Reference Objects.
     *
     * @return void
     */
    public function testValidateResponseObject(): void
    {
        $this->assertDoesNotThrow(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid']);
            \Cdd\Responses\validateResponseOrReferenceObject(['$ref' => '#/components/responses/Ref']);
            \Cdd\Responses\validateResponseOrReferenceObject([
                'description' => 'valid',
                'content' => [
                    'application/json' => [],
                ],
            ]);
            \Cdd\Responses\validateResponseOrReferenceObject([
                'description' => 'valid',
                'headers' => [
                    'Content-Type' => ['description' => 'Ignored'],
                    'X-Rate-Limit' => ['schema' => ['type' => 'integer']],
                ],
            ]);
        });

        $respWithLinks = [
            'description' => 'valid',
            'links' => [
                'LinkName' => ['operationId' => 'op'],
            ],
        ];
        $this->assertDoesNotThrow(function () use ($respWithLinks): void {
            \Cdd\Responses\validateResponseOrReferenceObject($respWithLinks);
        });
        $this->assertEquals(" * @return mixed
", \Cdd\Responses\emit(['200' => $respWithLinks]));
    }

    /**
     * Tests validation errors for Response Objects.
     *
     * @return void
     */
    public function testValidateResponseObjectFailures(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject('not array');
        }, \Cdd\Exceptions\CddException::class, 'Response must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject([]);
        }, \Cdd\Exceptions\CddException::class, 'Response must contain a "description" string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'content' => 'not array']);
        }, \Cdd\Exceptions\CddException::class, 'Response "content" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'headers' => 'not array']);
        }, \Cdd\Exceptions\CddException::class, 'Response "headers" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'links' => 'not array']);
        }, \Cdd\Exceptions\CddException::class, 'Response "links" must be a map');
    }

    /**
     * Tests validating valid Header or Reference Objects.
     *
     * @return void
     */
    public function testValidateHeaderObject(): void
    {
        $header = [
            'description' => 'valid header',
            'schema' => ['type' => 'string'],
        ];
        $this->assertDoesNotThrow(function () use ($header): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['$ref' => '#/components/headers/Ref']);
            \Cdd\Responses\validateHeaderOrReferenceObject($header);
            \Cdd\Responses\validateHeaderOrReferenceObject([
                'content' => [
                    'application/json' => [],
                ],
            ]);
            \Cdd\Responses\validateHeaderOrReferenceObject([
                'schema' => ['type' => 'string'],
                'required' => true,
                'deprecated' => false,
                'style' => 'simple',
                'explode' => false,
            ]);
        });
        $this->assertStringContainsString('valid header', \Cdd\Encoding\emit_header($header));
    }

    /**
     * Tests validation errors for Header Objects.
     *
     * @return void
     */
    public function testValidateHeaderObjectFailures(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject('not array');
        }, \Cdd\Exceptions\CddException::class, 'Header must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['name' => 'Name']);
        }, \Cdd\Exceptions\CddException::class, 'Header "name" MUST NOT be specified');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['in' => 'header']);
        }, \Cdd\Exceptions\CddException::class, 'Header "in" MUST NOT be specified');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['allowEmptyValue' => true]);
        }, \Cdd\Exceptions\CddException::class, 'Header "allowEmptyValue" MUST NOT be used');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['description' => 123]);
        }, \Cdd\Exceptions\CddException::class, 'Header "description" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['required' => 'yes']);
        }, \Cdd\Exceptions\CddException::class, 'Header "required" must be a boolean');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['deprecated' => 'yes']);
        }, \Cdd\Exceptions\CddException::class, 'Header "deprecated" must be a boolean');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'example' => '1', 'examples' => []]);
        }, \Cdd\Exceptions\CddException::class, 'Header cannot contain both "example" and "examples"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'content' => []]);
        }, \Cdd\Exceptions\CddException::class, 'Header cannot contain both "schema" and "content"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject([]);
        }, \Cdd\Exceptions\CddException::class, 'Header must contain either "schema" or "content"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'style' => 123]);
        }, \Cdd\Exceptions\CddException::class, 'Header "style" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'style' => 'matrix']);
        }, \Cdd\Exceptions\CddException::class, 'Header "style", if used, MUST be limited to "simple"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'explode' => 'yes']);
        }, \Cdd\Exceptions\CddException::class, 'Header "explode" must be a boolean');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => 'not array']);
        }, \Cdd\Exceptions\CddException::class, 'Header "schema" must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['content' => 'not array']);
        }, \Cdd\Exceptions\CddException::class, 'Header "content" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['content' => ['a' => [], 'b' => []]]);
        }, \Cdd\Exceptions\CddException::class, 'Header "content" map MUST only contain one entry');
    }

    /**
     * Tests validating valid Link or Reference Objects.
     *
     * @return void
     */
    public function testValidateLinkObject(): void
    {
        $this->assertDoesNotThrow(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['$ref' => '#/components/links/Ref']);
            \Cdd\Responses\validateLinkOrReferenceObject([
                'operationId' => 'op',
                'parameters' => ['a' => 'b'],
                'description' => 'link',
                'requestBody' => ['a'],
                'server' => ['url' => 'http://example.com'],
            ]);
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => '#/paths/~1foo/get']);
        });
    }

    /**
     * Tests validation errors for Link Objects.
     *
     * @return void
     */
    public function testValidateLinkObjectFailures(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject('not array');
        }, \Cdd\Exceptions\CddException::class, 'Link must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => 123]);
        }, \Cdd\Exceptions\CddException::class, 'Link "operationRef" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 123]);
        }, \Cdd\Exceptions\CddException::class, 'Link "operationId" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => 'a', 'operationId' => 'b']);
        }, \Cdd\Exceptions\CddException::class, 'Link cannot contain both "operationRef" and "operationId"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 'op', 'parameters' => 'not array']);
        }, \Cdd\Exceptions\CddException::class, 'Link "parameters" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 'op', 'description' => 123]);
        }, \Cdd\Exceptions\CddException::class, 'Link "description" must be a string');
    }
}
