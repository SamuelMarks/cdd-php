<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitSchemaTest extends TestCase {
    public function testParseSchemaDiscriminator() {
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
        $this->assertEquals("type", $parsed['components']['schemas']['Test']['discriminator']['propertyName']);
    }

    public function testParseSchemaDiscriminatorInvalid() {
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
        try {
            \Cdd\Openapi\parse($json);
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Discriminator must contain a "propertyName" string') !== false);
        }
    }

    public function testParseSchemaXMLInvalid() {
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
        try {
            \Cdd\Openapi\parse($json);
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'XML "name" must be a string') !== false);
        }
    }

    public function testSchemaTypeValidation() {
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
        try {
            \Cdd\Openapi\parse($json);
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Schema "type" must be a string or array of strings') !== false);
        }
    }

    public function testSchemaPropertiesValidation() {
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

    public function testSchemaItemsValidation() {
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
        $this->assertEquals("string", $parsed['components']['schemas']['Test']['items']['type']);
    }
}
