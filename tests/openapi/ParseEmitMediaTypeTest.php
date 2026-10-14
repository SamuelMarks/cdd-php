<?php

/**
 * Tests for Media Type and Encoding validation rules inside RequestBody content (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests Media Type validation errors during OpenAPI parsing.
 */
class ParseEmitMediaTypeTest extends TestCase
{
    /**
     * Tests error when Media Type contains both `example` and `examples`.
     *
     * @return void
     */
    public function testMediaTypeExampleAndExamples(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"example":1,"examples":{}}}}}}}}');
        }, \RuntimeException::class, 'Media Type cannot contain both "example" and "examples"');
    }

    /**
     * Tests error when Media Type contains both `encoding` and `prefixEncoding`.
     *
     * @return void
     */
    public function testMediaTypeEncodingAndPrefixEncoding(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"encoding":{},"prefixEncoding":[]}}}}}}}');
        }, \RuntimeException::class, 'Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"');
    }

    /**
     * Tests error when Media Type `encoding` is not a map.
     *
     * @return void
     */
    public function testMediaTypeEncodingInvalidType(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"encoding":"invalid"}}}}}}}');
        }, \RuntimeException::class, 'Media Type "encoding" must be a map');
    }

    /**
     * Tests error when nested Encoding `contentType` is not a string.
     *
     * @return void
     */
    public function testEncodingObjectInvalidContentType(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"encoding":{"prop":{"contentType":123}}}}}}}}}');
        }, \RuntimeException::class, 'Encoding "contentType" must be a string');
    }
}
