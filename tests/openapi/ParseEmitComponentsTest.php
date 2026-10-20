<?php

/**
 * Tests for OpenAPI Components Object validation errors (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests validation failures on invalid `components` fields.
 */
class ParseEmitComponentsTest extends TestCase
{
    /**
     * Tests error when `components` is not an object.
     *
     * @return void
     */
    public function testComponentsObjectInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":"invalid"}');
        }, \Cdd\Exceptions\CddException::class, 'Field "components" must be an object');
    }

    /**
     * Tests error when `components.schemas` is not a map.
     *
     * @return void
     */
    public function testComponentsObjectSchemasInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":"invalid"}}');
        }, \Cdd\Exceptions\CddException::class, "Components 'schemas' must be a map");
    }

    /**
     * Tests error when a component key violates the allowed regex pattern.
     *
     * @return void
     */
    public function testComponentsObjectKeysInvalidRegex(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"invalid space":{}}}}');
        }, \Cdd\Exceptions\CddException::class, "Components 'schemas' map keys must match");
    }

    /**
     * Tests error when a schema definition is neither an object nor a boolean.
     *
     * @return void
     */
    public function testComponentsObjectSchemaInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"ValidName":"invalid"}}}');
        }, \Cdd\Exceptions\CddException::class, 'Schema must be an object or boolean');
    }

    /**
     * Tests error when a component response is missing `description`.
     *
     * @return void
     */
    public function testComponentsResponseMissingDescription(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"responses":{"A":{}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Response must contain a "description" string');
    }

    /**
     * Tests error when a component example contains both `value` and `externalValue`.
     *
     * @return void
     */
    public function testComponentsExampleBothValues(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"examples":{"A":{"value":1,"externalValue":"a"}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Example cannot contain "externalValue" with "serializedValue" or "value"');
    }

    /**
     * Tests error when a component securityScheme is missing `type`.
     *
     * @return void
     */
    public function testComponentsSecuritySchemeMissingType(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"A":{}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Scheme must contain a "type" string');
    }
}
