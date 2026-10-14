<?php

/**
 * Tests for OpenAPI 3.2.0 specific features (`$self`, tag hierarchy, `query`, `additionalOperations`, `dataValue`, `nodeType`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests parsing and validating OpenAPI 3.2.0 specification features.
 */
class ParseEmitOAS320Test extends TestCase
{
    /**
     * Tests parsing OpenAPI 3.2.0 fields and structures.
     *
     * @return void
     */
    public function testParseOAS320Features(): void
    {
        $json = '{
            "openapi": "3.2.0",
            "$self": "https://example.com/api/openapi",
            "info": {
                "title": "API",
                "version": "1.0.0"
            },
            "tags": [
                {
                    "name": "child",
                    "summary": "Child Tag",
                    "parent": "parent",
                    "kind": "nav"
                }
            ],
            "webhooks": {
                "newPet": {
                    "post": {
                        "operationId": "newPet",
                        "requestBody": {
                            "description": "Information about a new pet in the system",
                            "content": {
                                "application/json": {
                                    "schema": {
                                        "$ref": "#/components/schemas/Pet"
                                    }
                                }
                            }
                        },
                        "responses": {
                            "200": {
                                "description": "Return a 200 status to indicate that the data was received successfully"
                            }
                        }
                    }
                }
            },
            "paths": {
                "/items": {
                    "query": {
                        "responses": {
                            "200": {
                                "description": "OK"
                            }
                        }
                    },
                    "additionalOperations": {
                        "COPY": {
                            "responses": {
                                "200": {
                                    "description": "Copied"
                                }
                            }
                        }
                    }
                }
            },
            "components": {
                "examples": {
                    "example1": {
                        "dataValue": {"foo": "bar"}
                    },
                    "example2": {
                        "serializedValue": "{\"foo\":\"bar\"}"
                    }
                },
                "schemas": {
                    "model1": {
                        "type": "object",
                        "xml": {
                            "nodeType": "element"
                        }
                    }
                },
                "links": {
                    "link1": {
                        "operationId": "op",
                        "requestBody": {"$ref": "#/components/requestBodies/req"}
                    }
                }
            }
        }';

        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('3.2.0', $parsed['openapi']);
        $this->assertEquals('https://example.com/api/openapi', $parsed['$self']);
        $this->assertEquals('child', $parsed['tags'][0]['name']);
        $this->assertEquals('parent', $parsed['tags'][0]['parent']);
        $this->assertEquals('nav', $parsed['tags'][0]['kind']);
        $this->assertTrue(isset($parsed['webhooks']['newPet']['post']['operationId']));
        $this->assertEquals('newPet', $parsed['webhooks']['newPet']['post']['operationId']);
        $this->assertTrue(isset($parsed['paths']['/items']['query']));
        $this->assertTrue(isset($parsed['paths']['/items']['additionalOperations']['COPY']));
        $this->assertTrue(isset($parsed['components']['examples']['example1']['dataValue']));
        $this->assertEquals('element', $parsed['components']['schemas']['model1']['xml']['nodeType']);
        $this->assertTrue(isset($parsed['components']['links']['link1']['requestBody']['$ref']));
    }
}
