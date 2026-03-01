<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitComponentsTest extends TestCase {
    public function testComponentsObjectInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":"invalid"}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "components" must be an object') !== false);
        }
    }

    public function testComponentsObjectSchemasInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":"invalid"}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), "Components 'schemas' must be a map") !== false);
        }
    }

    public function testComponentsObjectKeysInvalidRegex() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"invalid space":{}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), "Components 'schemas' map keys must match") !== false);
        }
    }

    public function testComponentsObjectSchemaInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"ValidName":"invalid"}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Schema must be an object or boolean') !== false);
        }
    }

    public function testComponentsResponseMissingDescription() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"responses":{"A":{}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Response must contain a "description" string') !== false);
        }
    }

    public function testComponentsExampleBothValues() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"examples":{"A":{"value":1,"externalValue":"a"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Example cannot contain "externalValue" with "serializedValue" or "value"') !== false);
        }
    }

    public function testComponentsSecuritySchemeMissingType() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"A":{}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme must contain a "type" string') !== false);
        }
    }
}
