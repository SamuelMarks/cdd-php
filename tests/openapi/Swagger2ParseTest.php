<?php

namespace Cdd\Tests\Openapi;

class Swagger2ParseTest extends \Cdd\Tests\Framework\TestCase {
    public function testParseSwagger2() {
        $json = '{
            "swagger": "2.0",
            "info": {
                "title": "Swagger Petstore",
                "version": "1.0.0"
            },
            "host": "petstore.swagger.io",
            "basePath": "/v1",
            "schemes": [
                "http"
            ],
            "consumes": [
                "application/json"
            ],
            "produces": [
                "application/json"
            ],
            "paths": {
                "/pets": {
                    "get": {
                        "summary": "List all pets",
                        "operationId": "listPets",
                        "responses": {
                            "200": {
                                "description": "A paged array of pets",
                                "schema": {
                                    "$ref": "#/definitions/Pets"
                                }
                            }
                        }
                    },
                    "post": {
                        "summary": "Create a pet",
                        "operationId": "createPets",
                        "parameters": [
                            {
                                "in": "body",
                                "name": "body",
                                "required": true,
                                "schema": {
                                    "$ref": "#/definitions/Pet"
                                }
                            }
                        ],
                        "responses": {
                            "201": {
                                "description": "Null response"
                            }
                        }
                    }
                }
            },
            "definitions": {
                "Pet": {
                    "type": "object",
                    "required": [
                        "id",
                        "name"
                    ],
                    "properties": {
                        "id": {
                            "type": "integer",
                            "format": "int64"
                        },
                        "name": {
                            "type": "string"
                        },
                        "tag": {
                            "type": "string"
                        }
                    }
                },
                "Pets": {
                    "type": "array",
                    "items": {
                        "$ref": "#/definitions/Pet"
                    }
                }
            }
        }';
        
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('3.2.0', $parsed['openapi']);
        $this->assertEquals('http://petstore.swagger.io/v1', $parsed['servers'][0]['url']);
        $this->assertTrue(isset($parsed['components']['schemas']['Pet']));
        $this->assertTrue(isset($parsed['paths']['/pets']['get']['responses']['200']['content']['application/json']['schema']['$ref']));
        $this->assertEquals('#/components/schemas/Pets', $parsed['paths']['/pets']['get']['responses']['200']['content']['application/json']['schema']['$ref']);
        $this->assertTrue(isset($parsed['paths']['/pets']['post']['requestBody']['content']['application/json']['schema']['$ref']));
        $this->assertEquals('#/components/schemas/Pet', $parsed['paths']['/pets']['post']['requestBody']['content']['application/json']['schema']['$ref']);
    }
}
