<?php

/**
 * Core tests for top-level OpenAPI document parsing, validation, and emission (`\Cdd\Openapi`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests top-level OpenAPI document validation rules, Info/Server/Tag/ExternalDocs/Reference validation, and version upgrades.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting a valid OpenAPI 3.2.0 document.
     *
     * @return void
     */
    public function testParseValid(): void
    {
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

    /**
     * Tests error on syntactically invalid JSON input.
     *
     * @return void
     */
    public function testParseInvalidJson(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{invalid json');
        }, \RuntimeException::class, 'Invalid JSON provided');
    }

    /**
     * Tests error when JSON root is not an object.
     *
     * @return void
     */
    public function testParseNotObject(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('"string"');
        }, \RuntimeException::class, 'OpenAPI document must be a JSON object');
    }

    /**
     * Tests error when `openapi` field is missing.
     *
     * @return void
     */
    public function testParseMissingOpenapi(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"info":{"title":"A","version":"1"},"paths":{}}');
        }, \RuntimeException::class, 'Missing REQUIRED field "openapi"');
    }

    /**
     * Tests error when `openapi` version is unsupported.
     *
     * @return void
     */
    public function testParseWrongOpenapiVersion(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"2.0.0","info":{"title":"A","version":"1"},"paths":{}}');
        }, \RuntimeException::class, 'Spec must be OpenAPI 3.0.x, 3.1.x, or 3.2.0');
    }

    /**
     * Tests error when `info` field is missing.
     *
     * @return void
     */
    public function testParseMissingInfo(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","paths":{}}');
        }, \RuntimeException::class, 'Missing REQUIRED field "info"');
    }

    /**
     * Tests error when document contains none of `paths`, `components`, or `webhooks`.
     *
     * @return void
     */
    public function testParseMissingPathsComponentsWebhooks(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"}}');
        }, \RuntimeException::class, 'Spec must contain paths, components, or webhooks');
    }

    /**
     * Tests error when `$self` is not a string.
     *
     * @return void
     */
    public function testParseInvalidSelf(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","$self":123,"info":{"title":"A","version":"1"},"paths":{}}');
        }, \RuntimeException::class, 'Field "$self" must be a string');
    }

    /**
     * Tests error when `info` is not an object.
     *
     * @return void
     */
    public function testParseInvalidInfo(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":"not-array","paths":{}}');
        }, \RuntimeException::class, 'Field "info" must be an Info Object');
    }

    /**
     * Tests error when `info.title` is missing.
     *
     * @return void
     */
    public function testParseInvalidInfoTitle(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"version":"1"},"paths":{}}');
        }, \RuntimeException::class, 'Info object must contain a "title" string');
    }

    /**
     * Tests error when `info.version` is missing.
     *
     * @return void
     */
    public function testParseInvalidInfoVersion(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A"},"paths":{}}');
        }, \RuntimeException::class, 'Info object must contain a "version" string');
    }

    /**
     * Tests error when `jsonSchemaDialect` is not a string.
     *
     * @return void
     */
    public function testParseInvalidJsonSchemaDialect(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"jsonSchemaDialect":123,"paths":{}}');
        }, \RuntimeException::class, 'Field "jsonSchemaDialect" must be a string');
    }

    /**
     * Tests error when `servers` is not an array.
     *
     * @return void
     */
    public function testParseInvalidServers(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":"invalid","paths":{}}');
        }, \RuntimeException::class, 'Field "servers" must be an array of Server Objects');
    }

    /**
     * Tests error when `security` is not an array.
     *
     * @return void
     */
    public function testParseInvalidSecurity(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"security":"invalid","paths":{}}');
        }, \RuntimeException::class, 'Field "security" must be an array of Security Requirement Objects');
    }

    /**
     * Tests error when `tags` is not an array.
     *
     * @return void
     */
    public function testParseInvalidTags(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":"invalid","paths":{}}');
        }, \RuntimeException::class, 'Field "tags" must be an array of Tag Objects');
    }

    /**
     * Tests error when `externalDocs` is not an object.
     *
     * @return void
     */
    public function testParseInvalidExternalDocs(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"externalDocs":"invalid","paths":{}}');
        }, \RuntimeException::class, 'Field "externalDocs" must be an External Documentation Object');
    }

    /**
     * Tests error when `externalDocs.url` is missing.
     *
     * @return void
     */
    public function testParseInvalidExternalDocsUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"externalDocs":{"description":"docs"},"paths":{}}');
        }, \RuntimeException::class, 'External Documentation Object must contain a "url" string');
    }

    /**
     * Tests error when `info.summary` is not a string.
     *
     * @return void
     */
    public function testParseInvalidInfoSummary(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","summary":123},"paths":{}}');
        }, \RuntimeException::class, 'Info "summary" must be a string');
    }

    /**
     * Tests error when `info.description` is not a string.
     *
     * @return void
     */
    public function testParseInvalidInfoDescription(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","description":123},"paths":{}}');
        }, \RuntimeException::class, 'Info "description" must be a string');
    }

    /**
     * Tests error when `info.termsOfService` is not a string.
     *
     * @return void
     */
    public function testParseInvalidInfoTermsOfService(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","termsOfService":123},"paths":{}}');
        }, \RuntimeException::class, 'Info "termsOfService" must be a string');
    }

    /**
     * Tests error when `info.contact` is not an object.
     *
     * @return void
     */
    public function testParseInvalidContact(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":"invalid"},"paths":{}}');
        }, \RuntimeException::class, 'Contact must be an object');
    }

    /**
     * Tests error when `contact.name` is not a string.
     *
     * @return void
     */
    public function testParseInvalidContactName(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":{"name":123}},"paths":{}}');
        }, \RuntimeException::class, 'Contact "name" must be a string');
    }

    /**
     * Tests error when `contact.url` is not a string.
     *
     * @return void
     */
    public function testParseInvalidContactUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":{"url":123}},"paths":{}}');
        }, \RuntimeException::class, 'Contact "url" must be a string');
    }

    /**
     * Tests error when `contact.email` is not a string.
     *
     * @return void
     */
    public function testParseInvalidContactEmail(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","contact":{"email":123}},"paths":{}}');
        }, \RuntimeException::class, 'Contact "email" must be a string');
    }

    /**
     * Tests error when `info.license` is not an object.
     *
     * @return void
     */
    public function testParseInvalidLicense(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":"invalid"},"paths":{}}');
        }, \RuntimeException::class, 'License must be an object');
    }

    /**
     * Tests error when `license.name` is missing.
     *
     * @return void
     */
    public function testParseInvalidLicenseName(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{}},"paths":{}}');
        }, \RuntimeException::class, 'License object must contain a "name" string');
    }

    /**
     * Tests error when `license.identifier` is not a string.
     *
     * @return void
     */
    public function testParseInvalidLicenseIdentifier(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{"name":"MIT","identifier":123}},"paths":{}}');
        }, \RuntimeException::class, 'License "identifier" must be a string');
    }

    /**
     * Tests error when `license.url` is not a string.
     *
     * @return void
     */
    public function testParseInvalidLicenseUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{"name":"MIT","url":123}},"paths":{}}');
        }, \RuntimeException::class, 'License "url" must be a string');
    }

    /**
     * Tests error when `license` specifies both `identifier` and `url`.
     *
     * @return void
     */
    public function testParseLicenseMutuallyExclusive(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1","license":{"name":"MIT","identifier":"MIT","url":"url"}},"paths":{}}');
        }, \RuntimeException::class, 'License "identifier" and "url" are mutually exclusive');
    }

    /**
     * Tests error when a Server Object is missing `url`.
     *
     * @return void
     */
    public function testParseServerMissingUrl(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{}],"paths":{}}');
        }, \RuntimeException::class, 'Server object must contain a "url" string');
    }

    /**
     * Tests error when Server `description` is not a string.
     *
     * @return void
     */
    public function testParseServerInvalidDescription(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","description":123}],"paths":{}}');
        }, \RuntimeException::class, 'Server "description" must be a string');
    }

    /**
     * Tests error when Server `name` is not a string.
     *
     * @return void
     */
    public function testParseServerInvalidName(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","name":123}],"paths":{}}');
        }, \RuntimeException::class, 'Server "name" must be a string');
    }

    /**
     * Tests error when Server `variables` is not a map.
     *
     * @return void
     */
    public function testParseServerInvalidVariables(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":"invalid"}],"paths":{}}');
        }, \RuntimeException::class, 'Server "variables" must be a map');
    }

    /**
     * Tests error when a Server Variable is not an object.
     *
     * @return void
     */
    public function testParseServerVariableInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":"invalid"}}],"paths":{}}');
        }, \RuntimeException::class, 'Server variable must be an object');
    }

    /**
     * Tests error when a Server Variable is missing `default`.
     *
     * @return void
     */
    public function testParseServerVariableMissingDefault(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{}}}],"paths":{}}');
        }, \RuntimeException::class, 'Server variable must contain a "default" string');
    }

    /**
     * Tests error when Server Variable `enum` is empty.
     *
     * @return void
     */
    public function testParseServerVariableInvalidEnum(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","enum":[]}}}],"paths":{}}');
        }, \RuntimeException::class, 'Server variable "enum" must be a non-empty array');
    }

    /**
     * Tests error when Server Variable `enum` values are not strings.
     *
     * @return void
     */
    public function testParseServerVariableInvalidEnumValues(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","enum":[123]}}}],"paths":{}}');
        }, \RuntimeException::class, 'Server variable "enum" values must be strings');
    }

    /**
     * Tests error when Server Variable `default` is not listed in `enum`.
     *
     * @return void
     */
    public function testParseServerVariableDefaultNotInEnum(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","enum":["443"]}}}],"paths":{}}');
        }, \RuntimeException::class, 'Server variable "default" value must exist in "enum"');
    }

    /**
     * Tests error when Server Variable `description` is not a string.
     *
     * @return void
     */
    public function testParseServerVariableInvalidDescription(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"servers":[{"url":"a","variables":{"port":{"default":"80","description":123}}}],"paths":{}}');
        }, \RuntimeException::class, 'Server variable "description" must be a string');
    }

    /**
     * Tests error when a Tag entry is not an object.
     *
     * @return void
     */
    public function testParseTagInvalid(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[123],"paths":{}}');
        }, \RuntimeException::class, 'Tag must be an object');
    }

    /**
     * Tests error when a Tag Object is missing `name`.
     *
     * @return void
     */
    public function testParseTagMissingName(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[{}],"paths":{}}');
        }, \RuntimeException::class, 'Tag object must contain a "name" string');
    }

    /**
     * Tests error when Tag `description` is not a string.
     *
     * @return void
     */
    public function testParseTagInvalidDescription(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[{"name":"t","description":123}],"paths":{}}');
        }, \RuntimeException::class, 'Tag "description" must be a string');
    }

    /**
     * Tests error when Tag `externalDocs` is missing `url`.
     *
     * @return void
     */
    public function testParseTagExternalDocs(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"tags":[{"name":"t","externalDocs":{"description":123}}],"paths":{}}');
        }, \RuntimeException::class, 'External Documentation Object must contain a "url" string');
    }

    /**
     * Tests error when ExternalDocs `description` is not a string.
     *
     * @return void
     */
    public function testParseExternalDocsDescription(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"externalDocs":{"url":"a","description":123},"paths":{}}');
        }, \RuntimeException::class, 'External Documentation "description" must be a string');
    }

    /**
     * Tests parsing a Reference Object with `summary` and `description` overrides.
     *
     * @return void
     */
    public function testParseReferenceObjectWithSummary(): void
    {
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
        $this->assertEquals('This is a summary override', $parsed['components']['schemas']['Test']['summary']);
        $this->assertEquals('This is a desc override', $parsed['components']['schemas']['Test']['description']);
    }

    /**
     * Tests error when Reference `summary` is not a string.
     *
     * @return void
     */
    public function testParseReferenceObjectInvalidSummary(): void
    {
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
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \RuntimeException::class, 'Reference "summary" must be a string');
    }

    /**
     * Tests error when Reference `$ref` is not a string.
     *
     * @return void
     */
    public function testRefMustBeString(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": {"title": "Test", "version": "1.0"},
            "components": {
                "schemas": {
                    "Test": {
                        "$ref": 123
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \RuntimeException::class, 'Reference "$ref" must be a string');
    }

    /**
     * Tests error when Reference `summary` is numeric.
     *
     * @return void
     */
    public function testRefSummaryMustBeString(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": {"title": "Test", "version": "1.0"},
            "components": {
                "schemas": {
                    "Test": {
                        "$ref": "#/components/schemas/Other",
                        "summary": 123
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \RuntimeException::class, 'Reference "summary" must be a string');
    }

    /**
     * Tests error when Reference `description` is not a string.
     *
     * @return void
     */
    public function testRefDescriptionMustBeString(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": {"title": "Test", "version": "1.0"},
            "components": {
                "schemas": {
                    "Test": {
                        "$ref": "#/components/schemas/Other",
                        "description": 123
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \RuntimeException::class, 'Reference "description" must be a string');
    }

    /**
     * Tests upgrading OpenAPI 3.0.x and 3.1.x documents to 3.2.0.
     *
     * @return void
     */
    public function testParseOpenapiVersions(): void
    {
        $parsed = \Cdd\Openapi\parse('{"openapi": "3.0.0", "info": {"title": "A", "version": "1"}, "paths": {}}');
        $this->assertEquals('3.2.0', $parsed['openapi']);

        $parsed = \Cdd\Openapi\parse('{"openapi": "3.1.0", "info": {"title": "A", "version": "1"}, "paths": {}}');
        $this->assertEquals('3.2.0', $parsed['openapi']);
    }
}
