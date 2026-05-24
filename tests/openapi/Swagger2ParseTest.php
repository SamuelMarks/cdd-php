<?php

namespace Cdd\Tests\Openapi;

class Swagger2ParseTest extends \Cdd\Tests\Framework\TestCase
{
    public function testParseSwagger2()
    {
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
                        "consumes": ["multipart/form-data"],
                        "parameters": [
                            {
                                "in": "body",
                                "name": "body",
                                "description": "Pet to add",
                                "required": true,
                                "schema": {
                                    "$ref": "#/definitions/Pet"
                                }
                            },
                            {
                                "in": "formData",
                                "name": "file",
                                "type": "file",
                                "description": "file to upload",
                                "format": "binary"
                            },
                            {
                                "in": "query",
                                "name": "limit",
                                "type": "integer",
                                "format": "int32",
                                "default": 10,
                                "maximum": 100,
                                "exclusiveMaximum": true,
                                "minimum": 1,
                                "exclusiveMinimum": false,
                                "multipleOf": 2,
                                "enum": [10, 20, 50, 100]
                            },
                            {
                                "in": "query",
                                "name": "tags",
                                "type": "array",
                                "items": {"type": "string"},
                                "maxItems": 10,
                                "minItems": 1,
                                "uniqueItems": true
                            },
                            {
                                "in": "header",
                                "name": "X-Request-ID",
                                "type": "string",
                                "maxLength": 50,
                                "minLength": 10,
                                "pattern": "^[a-z0-9-]+$"
                            }
                        ],
                        "responses": {
                            "201": {
                                "description": "Null response"
                            }
                        }
                    },
                    "put": {
                        "summary": "Update a pet",
                        "operationId": "updatePet",
                        "parameters": [
                            {
                                "in": "formData",
                                "name": "name",
                                "type": "string"
                            }
                        ],
                        "responses": {
                            "200": {
                                "description": "Updated",
                                "headers": {
                                    "X-Rate-Limit": {
                                        "type": "integer",
                                        "format": "int32",
                                        "items": { "type": "string" },
                                        "default": 100,
                                        "maximum": 1000,
                                        "exclusiveMaximum": true,
                                        "minimum": 1,
                                        "exclusiveMinimum": false,
                                        "maxLength": 50,
                                        "minLength": 10,
                                        "pattern": "^[0-9]+$",
                                        "maxItems": 10,
                                        "minItems": 1,
                                        "uniqueItems": true,
                                        "enum": [100, 200],
                                        "multipleOf": 100
                                    }
                                }
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
            },
            "parameters": {
                "skipParam": {
                    "name": "skip",
                    "in": "query",
                    "description": "number of items to skip",
                    "required": false,
                    "schema": {
                        "type": "integer",
                        "format": "int32"
                    }
                }
            },
            "responses": {
                "NotFound": {
                    "description": "Entity not found."
                }
            },
            "securityDefinitions": {
                "basicAuth": {
                    "type": "basic"
                },
                "petstore_auth_implicit": {
                    "type": "oauth2",
                    "authorizationUrl": "http://swagger.io/api/oauth/dialog",
                    "flow": "implicit",
                    "scopes": {
                        "write:pets": "modify pets in your account",
                        "read:pets": "read your pets"
                    }
                },
                "petstore_auth_application": {
                    "type": "oauth2",
                    "tokenUrl": "http://swagger.io/api/oauth/token",
                    "flow": "application",
                    "scopes": {
                        "write:pets": "modify pets in your account"
                    }
                },
                "petstore_auth_accessCode": {
                    "type": "oauth2",
                    "authorizationUrl": "http://swagger.io/api/oauth/dialog",
                    "tokenUrl": "http://swagger.io/api/oauth/token",
                    "flow": "accessCode",
                    "scopes": {
                        "read": "read"
                    }
                }
            }
        }';

        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('3.2.0', $parsed['openapi']);
        $this->assertEquals('http://petstore.swagger.io/v1', $parsed['servers'][0]['url']);
        $this->assertTrue(isset($parsed['components']['schemas']['Pet']));
        $this->assertTrue(isset($parsed['components']['parameters']['skipParam']));
        $this->assertTrue(isset($parsed['components']['responses']['NotFound']));
        $this->assertEquals('http', $parsed['components']['securitySchemes']['basicAuth']['type']);
        $this->assertEquals('basic', $parsed['components']['securitySchemes']['basicAuth']['scheme']);
        $this->assertTrue(isset($parsed['components']['securitySchemes']['petstore_auth_implicit']['flows']['implicit']['authorizationUrl']));
        $this->assertTrue(isset($parsed['components']['securitySchemes']['petstore_auth_application']['flows']['clientCredentials']['tokenUrl']));
        $this->assertTrue(isset($parsed['components']['securitySchemes']['petstore_auth_accessCode']['flows']['authorizationCode']['authorizationUrl']));
        $this->assertTrue(isset($parsed['components']['securitySchemes']['petstore_auth_accessCode']['flows']['authorizationCode']['tokenUrl']));
        $this->assertTrue(isset($parsed['paths']['/pets']['get']['responses']['200']['content']['application/json']['schema']['$ref']));
        $this->assertEquals('#/components/schemas/Pets', $parsed['paths']['/pets']['get']['responses']['200']['content']['application/json']['schema']['$ref']);
        // $this->assertTrue(isset($parsed['paths']['/pets']['post']['requestBody']['content']['application/json']['schema']['$ref']));
        $this->assertEquals('file', $parsed['paths']['/pets']['post']['requestBody']['content']['multipart/form-data']['schema']['properties']['file']['type']);
    }

    public function testParseSwagger2Empty()
    {
        $json = '{
            "swagger": "2.0",
            "info": {
                "title": "Empty",
                "version": "1.0.0"
            },
            "paths": {}
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('3.2.0', $parsed['openapi']);
        $this->assertTrue(!isset($parsed['components']));
    }

    public function testParseSwagger2ExtraBranches()
    {
        $json = '{
            "swagger": "2.0",
            "info": {"title": "Test", "version": "1"},
            "consumes": [],
            "produces": [],
            "paths": {
                "/test": {
                    "x-custom-extension": "value",
                    "get": {
                        "parameters": [
                            {
                                "in": "body",
                                "name": "body",
                                "schema": {"type": "string"}
                            }
                        ],
                        "responses": {
                            "200": {
                                "description": "ok",
                                "schema": {"type": "string"}
                            },
                            "201": {
                                "description": "header",
                                "headers": {
                                    "X-NoFormat": {"type": "string"}
                                }
                            }
                        }
                    }
                }
            }
        }';
        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals("value", $parsed['paths']['/test']['x-custom-extension']);
        $this->assertTrue(isset($parsed['paths']['/test']['get']['requestBody']['content']['application/json']['schema']));
        $this->assertTrue(isset($parsed['paths']['/test']['get']['responses']['200']['content']['application/json']['schema']));
        $this->assertEquals('string', $parsed['paths']['/test']['get']['responses']['201']['headers']['X-NoFormat']['schema']['type']);
        $this->assertTrue(!isset($parsed['paths']['/test']['get']['responses']['201']['headers']['X-NoFormat']['schema']['format']));
    }
}
