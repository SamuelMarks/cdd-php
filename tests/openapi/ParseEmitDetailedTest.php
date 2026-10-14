<?php

/**
 * Detailed validation tests for Paths, Operations, Parameters, Headers, Encoding, Responses, and OAuth2 (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests detailed OpenAPI 3.2.0 validation rules across nested specification objects.
 */
class ParseEmitDetailedTest extends TestCase
{
    /**
     * Tests error when `paths` is not an object.
     *
     * @return void
     */
    public function testPathsObjectInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":"invalid"}');
        }, \RuntimeException::class, 'Field "paths" must be an object');
    }

    /**
     * Tests error when a `paths` key does not start with `/`.
     *
     * @return void
     */
    public function testPathsObjectKeysWithoutSlash(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"no-slash":{}}}');
        }, \RuntimeException::class, 'Paths object keys must start with a forward slash (/)');
    }

    /**
     * Tests error when a Path Item is not an object.
     *
     * @return void
     */
    public function testPathItemObjectInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":"invalid"}}');
        }, \RuntimeException::class, 'Path Item must be an object');
    }

    /**
     * Tests error when an Operation is not an object.
     *
     * @return void
     */
    public function testOperationObjectInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":"invalid"}}}');
        }, \RuntimeException::class, 'Operation must be an object');
    }

    /**
     * Tests error when Operation `tags` items are not strings.
     *
     * @return void
     */
    public function testOperationObjectTags(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"tags":[123]}}}}');
        }, \RuntimeException::class, 'Operation "tags" items must be strings');
    }

    /**
     * Tests error when Operation `requestBody` is not an object.
     *
     * @return void
     */
    public function testOperationObjectRequestBody(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"requestBody":"invalid"}}}}');
        }, \RuntimeException::class, 'Request Body must be an object');
    }

    /**
     * Tests error when Operation `requestBody` is missing `content`.
     *
     * @return void
     */
    public function testOperationObjectRequestBodyNoContent(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"requestBody":{}}}}}');
        }, \RuntimeException::class, 'Request Body must contain a "content" map');
    }

    /**
     * Tests error when a Parameter is missing `name`.
     *
     * @return void
     */
    public function testParameterMissingName(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{}]}}}');
        }, \RuntimeException::class, 'Parameter must contain a "name" string');
    }

    /**
     * Tests error when a Parameter is missing `in`.
     *
     * @return void
     */
    public function testParameterMissingIn(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id"}]}}}');
        }, \RuntimeException::class, 'Parameter must contain an "in" string');
    }

    /**
     * Tests error when a Parameter has an invalid `in` location.
     *
     * @return void
     */
    public function testParameterInvalidIn(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"body"}]}}}');
        }, \RuntimeException::class, 'Parameter "in" must be one of');
    }

    /**
     * Tests error when a `path` Parameter does not have `required: true`.
     *
     * @return void
     */
    public function testParameterPathMissingRequired(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"path","required":false}]}}}');
        }, \RuntimeException::class, 'Parameter with in: path MUST have required: true');
    }

    /**
     * Tests error when a Parameter contains both `schema` and `content`.
     *
     * @return void
     */
    public function testParameterBothSchemaContent(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query","schema":{},"content":{}}]}}}');
        }, \RuntimeException::class, 'Parameter cannot contain both "schema" and "content"');
    }

    /**
     * Tests error when a Parameter contains neither `schema` nor `content`.
     *
     * @return void
     */
    public function testParameterNeitherSchemaContent(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query"}]}}}');
        }, \RuntimeException::class, 'Parameter must contain either "schema" or "content"');
    }

    /**
     * Tests error when a Parameter contains both `example` and `examples`.
     *
     * @return void
     */
    public function testParameterExampleExamples(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query","schema":{},"example":1,"examples":{"a":{}}}]}}}');
        }, \RuntimeException::class, 'Parameter cannot contain both "example" and "examples"');
    }

    /**
     * Tests error when `allowEmptyValue` is used on a non-query Parameter.
     *
     * @return void
     */
    public function testParameterAllowEmptyValueNotQuery(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"header","schema":{},"allowEmptyValue":true}]}}}');
        }, \RuntimeException::class, 'Parameter "allowEmptyValue" is only allowed for in: query');
    }

    /**
     * Tests error when `schema` or `style` is used with `in: querystring`.
     *
     * @return void
     */
    public function testParameterQuerystringWithSchemaFields(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"querystring","schema":{},"style":"form"}]}}}');
        }, \RuntimeException::class, 'schema, style, explode, allowReserved MUST NOT be used with in: querystring');
    }

    /**
     * Tests error when a Header contains both `example` and `examples`.
     *
     * @return void
     */
    public function testHeaderExampleExamples(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"MyHeader":{"schema":{},"example":1,"examples":{"a":{}}}}}}');
        }, \RuntimeException::class, 'Header cannot contain both "example" and "examples"');
    }

    /**
     * Tests error when MediaType contains both `encoding` and `prefixEncoding`.
     *
     * @return void
     */
    public function testEncodingMutualExclusivity(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"mediaTypes":{"MyMediaType":{"schema":{},"encoding":{"a":{}},"prefixEncoding":[{}]}}}}');
        }, \RuntimeException::class, 'Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"');
    }

    /**
     * Tests error when an Operation mixes `query` and `querystring` parameters.
     *
     * @return void
     */
    public function testOperationQueryQuerystringMutualExclusivity(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"parameters":[{"name":"a","in":"query","schema":{}},{"name":"b","in":"querystring","content":{}}]}}}}');
        }, \RuntimeException::class, 'query and querystring parameters are mutually exclusive');
    }

    /**
     * Tests error when an Operation defines multiple `querystring` parameters.
     *
     * @return void
     */
    public function testOperationMultipleQuerystring(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"parameters":[{"name":"a","in":"querystring","content":{}},{"name":"b","in":"querystring","content":{}}]}}}}');
        }, \RuntimeException::class, 'querystring parameter MUST NOT appear more than once');
    }

    /**
     * Tests error when Responses keys are invalid status codes.
     *
     * @return void
     */
    public function testResponsesObjectInvalidKeys(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"invalid":{"description":"ok"}}}}}}');
        }, \RuntimeException::class, 'Responses keys must be HTTP status codes, ranges like 2XX, or "default"');
    }

    /**
     * Tests valid Responses keys (`200`, `3XX`, `default`, `x-custom`).
     *
     * @return void
     */
    public function testResponsesObjectValidKeys(): void
    {
        $parsed = \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"},"3XX":{"description":"redirect"},"default":{"description":"default"},"x-custom":{}}}}}}');
        $this->assertEquals('ok', $parsed['paths']['/a']['get']['responses']['200']['description']);
    }

    /**
     * Tests error when OAuth2 implicit flow is missing `authorizationUrl`.
     *
     * @return void
     */
    public function testSecuritySchemeOauth2ImplicitRequiresAuthUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"o":{"type":"oauth2","flows":{"implicit":{"scopes":{}}}}}}}');
        }, \RuntimeException::class, "OAuth2 implicit flow requires an 'authorizationUrl' string");
    }

    /**
     * Tests error when OAuth2 password flow is missing `tokenUrl`.
     *
     * @return void
     */
    public function testSecuritySchemeOauth2PasswordRequiresTokenUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"o":{"type":"oauth2","flows":{"password":{"scopes":{}}}}}}}');
        }, \RuntimeException::class, "OAuth2 password flow requires a 'tokenUrl' string");
    }

    /**
     * Tests valid OAuth2 implicit and password flow definitions.
     *
     * @return void
     */
    public function testSecuritySchemeOauth2Valid(): void
    {
        $parsed = \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"o":{"type":"oauth2","flows":{"implicit":{"authorizationUrl":"http://a","scopes":{}},"password":{"tokenUrl":"http://b","scopes":{}}}}}}}');
        $this->assertEquals('http://a', $parsed['components']['securitySchemes']['o']['flows']['implicit']['authorizationUrl']);
    }
}
