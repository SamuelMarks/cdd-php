<?php

/**
 * Tests for parsing and upgrading Swagger 2.0 documents to OpenAPI 3.2.0 (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests Swagger 2.0 parsing and conversion into OpenAPI 3.2.0 structure.
 */
class Swagger2ParseTest extends TestCase
{
    /**
     * Tests parsing a full Swagger 2.0 document with definitions, parameters, responses, and securityDefinitions.
     *
     * @return void
     */
    public function testParseSwagger2(): void
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
        $this->assertEquals('file', $parsed['paths']['/pets']['post']['requestBody']['content']['multipart/form-data']['schema']['properties']['file']['type']);
    }

    /**
     * Tests parsing a minimal empty Swagger 2.0 document.
     *
     * @return void
     */
    public function testParseSwagger2Empty(): void
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

    /**
     * Tests Swagger 2.0 body parameter fallback and header schema conversion without format.
     *
     * @return void
     */
    public function testParseSwagger2ExtraBranches(): void
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
        $this->assertEquals('value', $parsed['paths']['/test']['x-custom-extension']);
        $this->assertTrue(isset($parsed['paths']['/test']['get']['requestBody']['content']['application/json']['schema']));
        $this->assertTrue(isset($parsed['paths']['/test']['get']['responses']['200']['content']['application/json']['schema']));
        $this->assertEquals('string', $parsed['paths']['/test']['get']['responses']['201']['headers']['X-NoFormat']['schema']['type']);
        $this->assertTrue(!isset($parsed['paths']['/test']['get']['responses']['201']['headers']['X-NoFormat']['schema']['format']));
    }

    /**
     * Tests Swagger 2.0 `collectionFormat` on Parameter Object, Items Object, and Header Object.
     *
     * @return void
     */
    public function testParseSwagger2CollectionFormats(): void
    {
        $json = '{
            "swagger": "2.0",
            "info": {"title": "CollectionFormat Test", "version": "1.0.0"},
            "produces": [],
            "parameters": {
                "SharedTags": {
                    "name": "sharedTags",
                    "in": "query",
                    "type": "array",
                    "collectionFormat": "csv",
                    "items": {"type": "string"}
                }
            },
            "responses": {
                "SharedResp": {
                    "description": "Shared response",
                    "schema": {"type": "string"},
                    "headers": {
                        "X-Shared-Header": {
                            "type": "array",
                            "collectionFormat": "pipes",
                            "items": {
                                "type": "array",
                                "collectionFormat": "csv",
                                "items": {"type": "string"}
                            }
                        }
                    }
                }
            },
            "paths": {
                "/items": {
                    "parameters": [
                        {
                            "name": "pathLevelTags",
                            "in": "header",
                            "type": "array",
                            "collectionFormat": "ssv",
                            "items": {"type": "string"}
                        }
                    ],
                    "get": {
                        "operationId": "filterItems",
                        "parameters": [
                            {
                                "name": "csvParam",
                                "in": "query",
                                "type": "array",
                                "collectionFormat": "csv",
                                "items": {
                                    "type": "array",
                                    "collectionFormat": "pipes",
                                    "items": {"type": "string"}
                                }
                            },
                            {
                                "name": "ssvParam",
                                "in": "query",
                                "type": "array",
                                "collectionFormat": "ssv",
                                "items": {"type": "string"}
                            },
                            {
                                "name": "tsvParam",
                                "in": "query",
                                "type": "array",
                                "collectionFormat": "tsv",
                                "items": {"type": "string"}
                            },
                            {
                                "name": "pipesParam",
                                "in": "header",
                                "type": "array",
                                "collectionFormat": "pipes",
                                "items": {"type": "string"}
                            },
                            {
                                "name": "multiParam",
                                "in": "query",
                                "type": "array",
                                "collectionFormat": "multi",
                                "items": {"type": "string"}
                            },
                            {
                                "name": "formTags",
                                "in": "formData",
                                "type": "array",
                                "collectionFormat": "csv",
                                "items": {
                                    "type": "array",
                                    "collectionFormat": "ssv",
                                    "items": {"type": "string"}
                                }
                            }
                        ],
                        "responses": {
                            "200": {
                                "description": "OK",
                                "headers": {
                                    "X-Rate-Tags": {
                                        "type": "array",
                                        "collectionFormat": "tsv",
                                        "items": {
                                            "type": "array",
                                            "collectionFormat": "csv",
                                            "items": {"type": "string"}
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }';

        $parsed = \Cdd\Openapi\parse($json);
        $this->assertEquals('form', $parsed['components']['parameters']['SharedTags']['style']);
        $this->assertEquals(false, $parsed['components']['parameters']['SharedTags']['explode']);
        $this->assertEquals('csv', $parsed['components']['parameters']['SharedTags']['x-collectionFormat']);
        $this->assertEquals('simple', $parsed['components']['responses']['SharedResp']['headers']['X-Shared-Header']['style']);
        $this->assertEquals('pipes', $parsed['components']['responses']['SharedResp']['headers']['X-Shared-Header']['x-collectionFormat']);
        $this->assertEquals('csv', $parsed['components']['responses']['SharedResp']['headers']['X-Shared-Header']['schema']['items']['collectionFormat']);

        $opParams = $parsed['paths']['/items']['get']['parameters'];
        $this->assertEquals('form', $opParams[0]['style']);
        $this->assertEquals('pipes', $opParams[0]['schema']['items']['collectionFormat']);
        $this->assertEquals('spaceDelimited', $opParams[1]['style']);
        $this->assertEquals('pipeDelimited', $opParams[2]['style']);
        $this->assertEquals('simple', $opParams[3]['style']);
        $this->assertEquals('form', $opParams[4]['style']);
        $this->assertEquals(true, $opParams[4]['explode']);

        $formProps = $parsed['paths']['/items']['get']['requestBody']['content']['application/x-www-form-urlencoded']['schema']['properties']['formTags'];
        $this->assertEquals('csv', $formProps['x-collectionFormat']);
        $this->assertEquals('ssv', $formProps['items']['collectionFormat']);

        $respHeader = $parsed['paths']['/items']['get']['responses']['200']['headers']['X-Rate-Tags'];
        $this->assertEquals('simple', $respHeader['style']);
        $this->assertEquals('tsv', $respHeader['x-collectionFormat']);
        $this->assertEquals('csv', $respHeader['schema']['items']['collectionFormat']);

        $jsonExamplesAndSchemes = '{
            "swagger": "2.0",
            "info": {"title": "Examples and Schemes", "version": "1.0.0"},
            "host": "api.example.com",
            "basePath": "/v2",
            "schemes": ["https", "http"],
            "x-root-ext": "root-val",
            "responses": {
                "SharedEx": {
                    "description": "Shared example response",
                    "schema": {"type": "object"},
                    "examples": {
                        "application/json": {"ok": true}
                    }
                }
            },
            "paths": {
                "/submit": {
                    "post": {
                        "operationId": "submitForm",
                        "schemes": ["https"],
                        "parameters": [
                            {
                                "name": "username",
                                "in": "formData",
                                "type": "string",
                                "required": true,
                                "description": "User handle",
                                "allowEmptyValue": false,
                                "x-param-ext": "custom-form"
                            }
                        ],
                        "responses": {
                            "200": {
                                "description": "Submitted",
                                "schema": {"type": "object"},
                                "examples": {
                                    "application/json": {"status": "submitted"}
                                }
                            }
                        }
                    }
                }
            }
        }';
        $parsedEx = \Cdd\Openapi\parse($jsonExamplesAndSchemes);
        $this->assertEquals('root-val', $parsedEx['x-root-ext']);
        $this->assertEquals(['ok' => true], $parsedEx['components']['responses']['SharedEx']['content']['application/json']['example']);
        $this->assertEquals('https://api.example.com/v2', $parsedEx['paths']['/submit']['post']['servers'][0]['url']);
        $formSchema = $parsedEx['paths']['/submit']['post']['requestBody']['content']['application/x-www-form-urlencoded']['schema'];
        $this->assertEquals(['username'], $formSchema['required']);
        $this->assertEquals('User handle', $formSchema['properties']['username']['description']);
        $this->assertEquals(false, $formSchema['properties']['username']['allowEmptyValue']);
        $this->assertEquals('custom-form', $formSchema['properties']['username']['x-param-ext']);
        $this->assertEquals(['status' => 'submitted'], $parsedEx['paths']['/submit']['post']['responses']['200']['content']['application/json']['example']);
    }
}
