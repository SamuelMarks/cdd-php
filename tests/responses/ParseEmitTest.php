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
        }, \RuntimeException::class, 'Response must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject([]);
        }, \RuntimeException::class, 'Response must contain a "description" string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'content' => 'not array']);
        }, \RuntimeException::class, 'Response "content" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'headers' => 'not array']);
        }, \RuntimeException::class, 'Response "headers" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'links' => 'not array']);
        }, \RuntimeException::class, 'Response "links" must be a map');
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
        }, \RuntimeException::class, 'Header must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['name' => 'Name']);
        }, \RuntimeException::class, 'Header "name" MUST NOT be specified');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['in' => 'header']);
        }, \RuntimeException::class, 'Header "in" MUST NOT be specified');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['allowEmptyValue' => true]);
        }, \RuntimeException::class, 'Header "allowEmptyValue" MUST NOT be used');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['description' => 123]);
        }, \RuntimeException::class, 'Header "description" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['required' => 'yes']);
        }, \RuntimeException::class, 'Header "required" must be a boolean');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['deprecated' => 'yes']);
        }, \RuntimeException::class, 'Header "deprecated" must be a boolean');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'example' => '1', 'examples' => []]);
        }, \RuntimeException::class, 'Header cannot contain both "example" and "examples"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'content' => []]);
        }, \RuntimeException::class, 'Header cannot contain both "schema" and "content"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject([]);
        }, \RuntimeException::class, 'Header must contain either "schema" or "content"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'style' => 123]);
        }, \RuntimeException::class, 'Header "style" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'style' => 'matrix']);
        }, \RuntimeException::class, 'Header "style", if used, MUST be limited to "simple"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'explode' => 'yes']);
        }, \RuntimeException::class, 'Header "explode" must be a boolean');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => 'not array']);
        }, \RuntimeException::class, 'Header "schema" must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['content' => 'not array']);
        }, \RuntimeException::class, 'Header "content" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateHeaderOrReferenceObject(['content' => ['a' => [], 'b' => []]]);
        }, \RuntimeException::class, 'Header "content" map MUST only contain one entry');
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
        }, \RuntimeException::class, 'Link must be an object');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => 123]);
        }, \RuntimeException::class, 'Link "operationRef" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 123]);
        }, \RuntimeException::class, 'Link "operationId" must be a string');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => 'a', 'operationId' => 'b']);
        }, \RuntimeException::class, 'Link cannot contain both "operationRef" and "operationId"');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 'op', 'parameters' => 'not array']);
        }, \RuntimeException::class, 'Link "parameters" must be a map');

        $this->assertThrows(function (): void {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 'op', 'description' => 123]);
        }, \RuntimeException::class, 'Link "description" must be a string');
    }
}
