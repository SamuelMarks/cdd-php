<?php

/**
 * Tests for OpenAPI Schema Object validation inside `components.schemas` (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests Schema Discriminator, XML, type, properties, and items validation via `\Cdd\Openapi\parse`.
 */
class ParseEmitSchemaTest extends TestCase
{
    /**
     * Tests parsing a valid Schema Discriminator Object.
     *
     * @return void
     */
    public function testParseSchemaDiscriminator(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "discriminator": {
                            "propertyName": "type"
                        }
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('type', $parsed['components']['schemas']['Test']['discriminator']['propertyName']);
    }

    /**
     * Tests error when Discriminator is missing `propertyName`.
     *
     * @return void
     */
    public function testParseSchemaDiscriminatorInvalid(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "discriminator": {
                            "mapping": {}
                        }
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \Cdd\Exceptions\CddException::class, 'Discriminator must contain a "propertyName" string');
    }

    /**
     * Tests error when XML `name` is not a string.
     *
     * @return void
     */
    public function testParseSchemaXMLInvalid(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "xml": {
                            "name": 123
                        }
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \Cdd\Exceptions\CddException::class, 'XML "name" must be a string');
    }

    /**
     * Tests error when Schema `type` is neither a string nor an array of strings.
     *
     * @return void
     */
    public function testSchemaTypeValidation(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "type": 123
                    }
                }
            }
        }';
        $this->assertThrows(function () use ($json): void {
            \Cdd\Openapi\parse($json);
        }, \Cdd\Exceptions\CddException::class, 'Schema "type" must be a string or array of strings');
    }

    /**
     * Tests parsing a Schema Object with an empty `properties` map.
     *
     * @return void
     */
    public function testSchemaPropertiesValidation(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "properties": []
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals([], $parsed['components']['schemas']['Test']['properties']);
    }

    /**
     * Tests parsing a Schema Object with `items`.
     *
     * @return void
     */
    public function testSchemaItemsValidation(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "info": { "title": "Test", "version": "1.0" },
            "paths": {},
            "components": {
                "schemas": {
                    "Test": {
                        "items": { "type": "string" }
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('string', $parsed['components']['schemas']['Test']['items']['type']);
    }
}
