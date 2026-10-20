<?php

/**
 * Tests for Header, Link, Response, SecurityScheme, and Encoding validation (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests additional validation rules on Headers, Links, Responses, SecuritySchemes, and Encodings.
 */
class ParseEmitFinalDetailedTest extends TestCase
{
    /**
     * Tests error when a Header contains both `schema` and `content`.
     *
     * @return void
     */
    public function testHeaderObjectBothSchemaContent(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"schema":{},"content":{}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Header cannot contain both "schema" and "content"');
    }

    /**
     * Tests error when a Header contains neither `schema` nor `content`.
     *
     * @return void
     */
    public function testHeaderObjectNeitherSchemaContent(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"description":"Test"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Header must contain either "schema" or "content"');
    }

    /**
     * Tests validating a Callback Object with numeric/expression keys.
     *
     * @return void
     */
    public function testCallbackObjectKeys(): void
    {
        $this->assertDoesNotThrow(function (): void {
            \Cdd\Operations\validateCallbackOrReferenceObject(['200' => ['get' => ['responses' => ['200' => ['description' => 'ok']]]]]);
        });
    }

    /**
     * Tests error when a Link Object contains both `operationRef` and `operationId`.
     *
     * @return void
     */
    public function testLinkObjectBothOperationRefId(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"links":{"L1":{"operationRef":"ref","operationId":"id"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Link cannot contain both "operationRef" and "operationId"');
    }

    /**
     * Tests error when Response `headers` is not a map.
     *
     * @return void
     */
    public function testResponseObjectHeaders(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"responses":{"R1":{"description":"Test","headers":"invalid"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Response "headers" must be a map');
    }

    /**
     * Tests error when Response `links` is not a map.
     *
     * @return void
     */
    public function testResponseObjectLinks(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"responses":{"R1":{"description":"Test","links":"invalid"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Response "links" must be a map');
    }

    /**
     * Tests error when SecurityScheme `type` is invalid.
     *
     * @return void
     */
    public function testSecuritySchemeInvalidType(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"invalid"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme "type" must be one of');
    }

    /**
     * Tests error when `apiKey` SecurityScheme is missing `name`.
     *
     * @return void
     */
    public function testSecuritySchemeApiKeyMissingName(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"apiKey"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme "apiKey" requires a "name" string');
    }

    /**
     * Tests error when `apiKey` SecurityScheme is missing `in`.
     *
     * @return void
     */
    public function testSecuritySchemeApiKeyMissingIn(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"apiKey","name":"key"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme "apiKey" requires an "in" string');
    }

    /**
     * Tests error when `http` SecurityScheme is missing `scheme`.
     *
     * @return void
     */
    public function testSecuritySchemeHttpMissingScheme(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"http"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme "http" requires a "scheme" string');
    }

    /**
     * Tests error when `oauth2` SecurityScheme is missing `flows`.
     *
     * @return void
     */
    public function testSecuritySchemeOauth2MissingFlows(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"oauth2"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme "oauth2" requires a "flows" map');
    }

    /**
     * Tests error when `openIdConnect` SecurityScheme is missing `openIdConnectUrl`.
     *
     * @return void
     */
    public function testSecuritySchemeOpenIdConnectMissingUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"openIdConnect"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme "openIdConnect" requires an "openIdConnectUrl" string');
    }

    /**
     * Tests error when Encoding `contentType` is not a string.
     *
     * @return void
     */
    public function testEncodingObjectInvalidContentType(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"contentType":123}}}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Encoding "contentType" must be a string');
    }

    /**
     * Tests error when Encoding `headers` is not a map.
     *
     * @return void
     */
    public function testEncodingObjectInvalidHeaders(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"headers":123}}}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Encoding "headers" must be a map');
    }

    /**
     * Tests error when Encoding `style` is not a string.
     *
     * @return void
     */
    public function testEncodingObjectInvalidStyle(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"style":123}}}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Encoding "style" must be a string');
    }

    /**
     * Tests error when Encoding `explode` is not a boolean.
     *
     * @return void
     */
    public function testEncodingObjectInvalidExplode(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"explode":123}}}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Encoding "explode" must be a boolean');
    }

    /**
     * Tests error when Encoding `allowReserved` is not a boolean.
     *
     * @return void
     */
    public function testEncodingObjectInvalidAllowReserved(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"allowReserved":123}}}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Encoding "allowReserved" must be a boolean');
    }
}
