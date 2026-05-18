<?php

declare(strict_types=1);

namespace Cdd\Tests\Responses;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    protected function assertThrows(\Closure $closure, string $exceptionClass, ?string $exceptionMessage = null)
    {
        try {
            $closure();
        } catch (\Throwable $e) {
            if (!($e instanceof $exceptionClass)) {
                throw new \Exception("Expected exception $exceptionClass, got " . get_class($e));
            }
            if ($exceptionMessage !== null && $e->getMessage() !== $exceptionMessage) {
                throw new \Exception("Expected exception message '$exceptionMessage', got '{$e->getMessage()}'");
            }
            return;
        }
        throw new \Exception("Expected exception $exceptionClass was not thrown");
    }

    public function testParseAndEmit()
    {
        $res = \Cdd\Responses\parse('201', 'User', 'Created user');

        $this->assertEquals('Created user', $res['201']['description']);
        $this->assertEquals('#/components/schemas/User', $res['201']['content']['application/json']['schema']['$ref']);

        $emitted = \Cdd\Responses\emit($res);
        $this->assertEquals(" * @return User\n", $emitted);
    }

    public function testEmitTypes()
    {
        $res = \Cdd\Responses\parse('200', 'int', 'int val');
        $this->assertEquals(" * @return int\n", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'float', 'float val');
        $this->assertEquals(" * @return float\n", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'bool', 'bool val');
        $this->assertEquals(" * @return bool\n", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'array', 'array val');
        $this->assertEquals(" * @return array\n", \Cdd\Responses\emit($res));

        $res = \Cdd\Responses\parse('200', 'object', 'obj val');
        $this->assertEquals(" * @return object\n", \Cdd\Responses\emit($res));

        $res = [
            '200' => [
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'unknown_type']
                    ]
                ]
            ]
        ];
        $this->assertEquals(" * @return mixed\n", \Cdd\Responses\emit($res));

        $res = [
            '200' => [
                'content' => [
                    'application/json' => []
                ]
            ]
        ];
        $this->assertEquals(" * @return mixed\n", \Cdd\Responses\emit($res));
    }

    public function testValidateResponseObject()
    {
        \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid']);
        \Cdd\Responses\validateResponseOrReferenceObject(['$ref' => '#/components/responses/Ref']);

        \Cdd\Responses\validateResponseOrReferenceObject([
            'description' => 'valid',
            'content' => [
                'application/json' => []
            ]
        ]);

        // Headers
        \Cdd\Responses\validateResponseOrReferenceObject([
            'description' => 'valid',
            'headers' => [
                'Content-Type' => ['description' => 'Ignored'],
                'X-Rate-Limit' => ['schema' => ['type' => 'integer']]
            ]
        ]);

        // Links
        \Cdd\Responses\validateResponseOrReferenceObject([
            'description' => 'valid',
            'links' => [
                'LinkName' => ['operationId' => 'op']
            ]
        ]);
    }

    public function testValidateResponseObjectFailures()
    {
        $this->assertThrows(function () {
            \Cdd\Responses\validateResponseOrReferenceObject('not array');
        }, \RuntimeException::class, 'Response must be an object');

        $this->assertThrows(function () {
            \Cdd\Responses\validateResponseOrReferenceObject([]);
        }, \RuntimeException::class, 'Response must contain a "description" string');

        $this->assertThrows(function () {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'content' => 'not array']);
        }, \RuntimeException::class, 'Response "content" must be a map');

        $this->assertThrows(function () {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'headers' => 'not array']);
        }, \RuntimeException::class, 'Response "headers" must be a map');

        $this->assertThrows(function () {
            \Cdd\Responses\validateResponseOrReferenceObject(['description' => 'valid', 'links' => 'not array']);
        }, \RuntimeException::class, 'Response "links" must be a map');
    }

    public function testValidateHeaderObject()
    {
        \Cdd\Responses\validateHeaderOrReferenceObject(['$ref' => '#/components/headers/Ref']);
        \Cdd\Responses\validateHeaderOrReferenceObject([
            'description' => 'valid header',
            'schema' => ['type' => 'string']
        ]);
        \Cdd\Responses\validateHeaderOrReferenceObject([
            'content' => [
                'application/json' => []
            ]
        ]);
        \Cdd\Responses\validateHeaderOrReferenceObject([
            'schema' => ['type' => 'string'],
            'required' => true,
            'deprecated' => false,
            'style' => 'simple',
            'explode' => false
        ]);
    }

    public function testValidateHeaderObjectFailures()
    {
        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject('not array');
        }, \RuntimeException::class, 'Header must be an object');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['name' => 'Name']);
        }, \RuntimeException::class, 'Header "name" MUST NOT be specified');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['in' => 'header']);
        }, \RuntimeException::class, 'Header "in" MUST NOT be specified');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['allowEmptyValue' => true]);
        }, \RuntimeException::class, 'Header "allowEmptyValue" MUST NOT be used');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['description' => 123]);
        }, \RuntimeException::class, 'Header "description" must be a string');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['required' => 'yes']);
        }, \RuntimeException::class, 'Header "required" must be a boolean');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['deprecated' => 'yes']);
        }, \RuntimeException::class, 'Header "deprecated" must be a boolean');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'example' => '1', 'examples' => []]);
        }, \RuntimeException::class, 'Header cannot contain both "example" and "examples"');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'content' => []]);
        }, \RuntimeException::class, 'Header cannot contain both "schema" and "content"');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject([]);
        }, \RuntimeException::class, 'Header must contain either "schema" or "content"');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'style' => 123]);
        }, \RuntimeException::class, 'Header "style" must be a string');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'style' => 'matrix']);
        }, \RuntimeException::class, 'Header "style", if used, MUST be limited to "simple"');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => [], 'explode' => 'yes']);
        }, \RuntimeException::class, 'Header "explode" must be a boolean');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['schema' => 'not array']);
        }, \RuntimeException::class, 'Header "schema" must be an object');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['content' => 'not array']);
        }, \RuntimeException::class, 'Header "content" must be a map');

        $this->assertThrows(function () {
            \Cdd\Responses\validateHeaderOrReferenceObject(['content' => ['a' => [], 'b' => []]]);
        }, \RuntimeException::class, 'Header "content" map MUST only contain one entry');
    }

    public function testValidateLinkObject()
    {
        \Cdd\Responses\validateLinkOrReferenceObject(['$ref' => '#/components/links/Ref']);
        \Cdd\Responses\validateLinkOrReferenceObject([
            'operationId' => 'op',
            'parameters' => ['a' => 'b'],
            'description' => 'link',
            'requestBody' => ['a'],
            'server' => ['url' => 'http://example.com']
        ]);
        \Cdd\Responses\validateLinkOrReferenceObject([
            'operationRef' => '#/paths/~1foo/get'
        ]);
    }

    public function testValidateLinkObjectFailures()
    {
        $this->assertThrows(function () {
            \Cdd\Responses\validateLinkOrReferenceObject('not array');
        }, \RuntimeException::class, 'Link must be an object');

        $this->assertThrows(function () {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => 123]);
        }, \RuntimeException::class, 'Link "operationRef" must be a string');

        $this->assertThrows(function () {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 123]);
        }, \RuntimeException::class, 'Link "operationId" must be a string');

        $this->assertThrows(function () {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationRef' => 'a', 'operationId' => 'b']);
        }, \RuntimeException::class, 'Link cannot contain both "operationRef" and "operationId"');

        $this->assertThrows(function () {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 'op', 'parameters' => 'not array']);
        }, \RuntimeException::class, 'Link "parameters" must be a map');

        $this->assertThrows(function () {
            \Cdd\Responses\validateLinkOrReferenceObject(['operationId' => 'op', 'description' => 123]);
        }, \RuntimeException::class, 'Link "description" must be a string');
    }
}
