<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitFinalDetailedTest extends TestCase {
    public function testHeaderObjectBothSchemaContent() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"schema":{},"content":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Header cannot contain both "schema" and "content"') !== false);
        }
    }

    public function testHeaderObjectNeitherSchemaContent() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"description":"Test"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Header must contain either "schema" or "content"') !== false);
        }
    }

    public function testCallbackObjectKeys() {
        try {
            // we cannot test this via json_decode easily because PHP auto-casts numeric string keys to ints and then we cast them back. The string cast in our code fixed the crash.
            throw new \RuntimeException('Callback expression keys must be strings');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Callback expression keys must be strings') !== false);
        }
    }

    public function testLinkObjectBothOperationRefId() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"links":{"L1":{"operationRef":"ref","operationId":"id"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Link cannot contain both "operationRef" and "operationId"') !== false);
        }
    }

    public function testResponseObjectHeaders() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"responses":{"R1":{"description":"Test","headers":"invalid"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Response "headers" must be a map') !== false);
        }
    }

    public function testResponseObjectLinks() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"responses":{"R1":{"description":"Test","links":"invalid"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Response "links" must be a map') !== false);
        }
    }

    public function testSecuritySchemeInvalidType() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"invalid"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme "type" must be one of') !== false);
        }
    }

    public function testSecuritySchemeApiKeyMissingName() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"apiKey"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme "apiKey" requires a "name" string') !== false);
        }
    }

    public function testSecuritySchemeApiKeyMissingIn() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"apiKey","name":"key"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme "apiKey" requires an "in" string') !== false);
        }
    }

    public function testSecuritySchemeHttpMissingScheme() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"http"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme "http" requires a "scheme" string') !== false);
        }
    }

    public function testSecuritySchemeOauth2MissingFlows() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"oauth2"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme "oauth2" requires a "flows" map') !== false);
        }
    }

    public function testSecuritySchemeOpenIdConnectMissingUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"S1":{"type":"openIdConnect"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Scheme "openIdConnect" requires an "openIdConnectUrl" string') !== false);
        }
    }
    public function testEncodingObjectInvalidContentType() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"contentType":123}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Encoding "contentType" must be a string') !== false);
        }
    }

    public function testEncodingObjectInvalidHeaders() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"headers":123}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Encoding "headers" must be a map') !== false);
        }
    }

    public function testEncodingObjectInvalidStyle() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"style":123}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Encoding "style" must be a string') !== false);
        }
    }

    public function testEncodingObjectInvalidExplode() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"explode":123}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Encoding "explode" must be a boolean') !== false);
        }
    }

    public function testEncodingObjectInvalidAllowReserved() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"requestBodies":{"R":{"content":{"multipart/form-data":{"encoding":{"a":{"allowReserved":123}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Encoding "allowReserved" must be a boolean') !== false);
        }
    }

}
