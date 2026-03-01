<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseValid() {
        $json = '{
            "openapi": "3.2.0",
            "$self": "https://example.com/openapi.json",
            "info": {
                "title": "Test API",
                "version": "1.0.0"
            },
            "jsonSchemaDialect": "https://json-schema.org/draft/2020-12/schema",
            "servers": [
                {"url": "https://api.example.com"}
            ],
            "paths": {},
            "webhooks": {},
            "components": {},
            "security": [
                {"api_key": []}
            ],
            "tags": [
                {"name": "test"}
            ],
            "externalDocs": {
                "url": "https://example.com/docs"
            }
        }';
        
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('3.2.0', $parsed['openapi']);
        $this->assertEquals('Test API', $parsed['info']['title']);
        $this->assertEquals('https://example.com/docs', $parsed['externalDocs']['url']);
        
        $emitted = \Cdd\Openapi\emit($parsed);
        $this->assertTrue(strpos($emitted, '"openapi": "3.2.0"') !== false);
    }

    public function testParseInvalidJson() {
        try {
            \Cdd\Openapi\parse('{invalid json');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Invalid JSON provided') !== false);
        }
    }

    public function testParseNotObject() {
        try {
            \Cdd\Openapi\parse('"string"');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'OpenAPI document must be a JSON object') !== false);
        }
    }

    public function testParseMissingOpenapi() {
        try {
            \Cdd\Openapi\parse('{"info":{"title":"A","version":"1"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Missing REQUIRED field "openapi"') !== false);
        }
    }

    public function testParseWrongOpenapiVersion() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.0.0","info":{"title":"A","version":"1"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Spec must be OpenAPI 3.2.0') !== false);
        }
    }

    public function testParseMissingInfo() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Missing REQUIRED field "info"') !== false);
        }
    }

    public function testParseMissingPathsComponentsWebhooks() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Spec must contain paths, components, or webhooks') !== false);
        }
    }

    public function testParseInvalidSelf() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","$self":123,"info":{"title":"A","version":"1"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "$self" must be a string') !== false);
        }
    }

    public function testParseInvalidInfo() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":"not-array","paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "info" must be an Info Object') !== false);
        }
    }

    public function testParseInvalidInfoTitle() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"version":"1"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Info object must contain a "title" string') !== false);
        }
    }

    public function testParseInvalidInfoVersion() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Info object must contain a "version" string') !== false);
        }
    }

    public function testParseInvalidJsonSchemaDialect() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"jsonSchemaDialect":123,"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "jsonSchemaDialect" must be a string') !== false);
        }
    }

    public function testParseInvalidServers() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":"invalid","paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "servers" must be an array of Server Objects') !== false);
        }
    }

    public function testParseInvalidSecurity() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"security":"invalid","paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "security" must be an array of Security Requirement Objects') !== false);
        }
    }

    public function testParseInvalidTags() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":"invalid","paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "tags" must be an array of Tag Objects') !== false);
        }
    }

    public function testParseInvalidExternalDocs() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"externalDocs":"invalid","paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "externalDocs" must be an External Documentation Object') !== false);
        }
    }

    public function testParseInvalidExternalDocsUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"externalDocs":{"description":"docs"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'External Documentation Object must contain a "url" string') !== false);
        }
    }

    public function testParseInvalidInfoSummary() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","summary":123},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Info "summary" must be a string') !== false);
        }
    }

    public function testParseInvalidInfoDescription() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","description":123},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Info "description" must be a string') !== false);
        }
    }

    public function testParseInvalidInfoTermsOfService() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","termsOfService":123},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Info "termsOfService" must be a string') !== false);
        }
    }

    public function testParseInvalidContact() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":"invalid"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Contact must be an object') !== false);
        }
    }

    public function testParseInvalidContactName() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":{"name":123}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Contact "name" must be a string') !== false);
        }
    }

    public function testParseInvalidContactUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":{"url":123}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Contact "url" must be a string') !== false);
        }
    }

    public function testParseInvalidContactEmail() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":{"email":123}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Contact "email" must be a string') !== false);
        }
    }

    public function testParseInvalidLicense() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":"invalid"},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'License must be an object') !== false);
        }
    }

    public function testParseInvalidLicenseName() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'License object must contain a "name" string') !== false);
        }
    }

    public function testParseInvalidLicenseIdentifier() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{"name":"MIT","identifier":123}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'License "identifier" must be a string') !== false);
        }
    }

    public function testParseInvalidLicenseUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{"name":"MIT","url":123}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'License "url" must be a string') !== false);
        }
    }

    public function testParseLicenseMutuallyExclusive() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{"name":"MIT","identifier":"MIT","url":"url"}},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'License "identifier" and "url" are mutually exclusive') !== false);
        }
    }

    public function testParseServerMissingUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server object must contain a "url" string') !== false);
        }
    }

    public function testParseServerInvalidDescription() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","description":123}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server "description" must be a string') !== false);
        }
    }

    public function testParseServerInvalidName() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","name":123}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server "name" must be a string') !== false);
        }
    }

    public function testParseServerInvalidVariables() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":"invalid"}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server "variables" must be a map') !== false);
        }
    }

    public function testParseServerVariableInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":"invalid"}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server variable must be an object') !== false);
        }
    }

    public function testParseServerVariableMissingDefault() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{}}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server variable must contain a "default" string') !== false);
        }
    }

    public function testParseServerVariableInvalidEnum() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","enum":[]}}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server variable "enum" must be a non-empty array') !== false);
        }
    }

    public function testParseServerVariableInvalidEnumValues() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","enum":[123]}}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server variable "enum" values must be strings') !== false);
        }
    }

    public function testParseServerVariableDefaultNotInEnum() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","enum":["443"]}}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server variable "default" value must exist in "enum"') !== false);
        }
    }

    public function testParseServerVariableInvalidDescription() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","description":123}}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Server variable "description" must be a string') !== false);
        }
    }

    public function testParseTagInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[123],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Tag must be an object') !== false);
        }
    }

    public function testParseTagMissingName() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[{}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Tag object must contain a "name" string') !== false);
        }
    }

    public function testParseTagInvalidDescription() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[{"name":"t","description":123}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Tag "description" must be a string') !== false);
        }
    }

    public function testParseTagExternalDocs() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[{"name":"t","externalDocs":{"description":123}}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'External Documentation Object must contain a "url" string') !== false);
        }
    }

    public function testParseExternalDocsDescription() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"externalDocs":{"url":"a","description":123},"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'External Documentation "description" must be a string') !== false);
        }
    }

    public function testParseReferenceObjectWithSummary() {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "$ref": "#/components/schemas/Other",
                        "summary": "This is a summary override",
                        "description": "This is a desc override"
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals("This is a summary override", $parsed['components']['schemas']['Test']['summary']);
        $this->assertEquals("This is a desc override", $parsed['components']['schemas']['Test']['description']);
    }

    public function testParseReferenceObjectInvalidSummary() {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "$ref": "#/components/schemas/Other",
                        "summary": 123
                    }
                }
            }
        }';
        try {
            \Cdd\Openapi\parse($json);
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Reference "summary" must be a string') !== false);
        }
    }
}
