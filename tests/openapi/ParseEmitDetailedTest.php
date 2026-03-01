<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitDetailedTest extends TestCase {
    public function testPathsObjectInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":"invalid"}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "paths" must be an object') !== false);
        }
    }

    public function testPathsObjectKeysWithoutSlash() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"no-slash":{}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Paths object keys must start with a forward slash (/)') !== false);
        }
    }

    public function testPathItemObjectInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":"invalid"}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Path Item must be an object') !== false);
        }
    }

    public function testOperationObjectInvalid() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":"invalid"}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Operation must be an object') !== false);
        }
    }

    public function testOperationObjectTags() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"tags":[123]}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Operation "tags" items must be strings') !== false);
        }
    }

    public function testOperationObjectRequestBody() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"requestBody":"invalid"}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Request Body must be an object') !== false);
        }
    }

    public function testOperationObjectRequestBodyNoContent() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"requestBody":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Request Body must contain a "content" map') !== false);
        }
    }

    public function testParameterMissingName() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter must contain a "name" string') !== false);
        }
    }

    public function testParameterMissingIn() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id"}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter must contain an "in" string') !== false);
        }
    }

    public function testParameterInvalidIn() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"body"}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter "in" must be one of') !== false);
        }
    }

    public function testParameterPathMissingRequired() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"path","required":false}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter with in: path MUST have required: true') !== false);
        }
    }

    public function testParameterBothSchemaContent() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query","schema":{},"content":{}}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter cannot contain both "schema" and "content"') !== false);
        }
    }

    public function testParameterNeitherSchemaContent() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query"}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter must contain either "schema" or "content"') !== false);
        }
    }
    public function testParameterExampleExamples() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query","schema":{},"example":1,"examples":{"a":{}}}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter cannot contain both "example" and "examples"') !== false);
        }
    }

    public function testParameterAllowEmptyValueNotQuery() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"header","schema":{},"allowEmptyValue":true}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Parameter "allowEmptyValue" is only allowed for in: query') !== false);
        }
    }

    public function testParameterQuerystringWithSchemaFields() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"querystring","schema":{},"style":"form"}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'schema, style, explode, allowReserved MUST NOT be used with in: querystring') !== false);
        }
    }

    public function testHeaderExampleExamples() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"MyHeader":{"schema":{},"example":1,"examples":{"a":{}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Header cannot contain both "example" and "examples"') !== false);
        }
    }

    public function testEncodingMutualExclusivity() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"mediaTypes":{"MyMediaType":{"schema":{},"encoding":{"a":{}},"prefixEncoding":[{}]}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"') !== false);
        }
    }

    public function testOperationQueryQuerystringMutualExclusivity() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"parameters":[{"name":"a","in":"query","schema":{}},{"name":"b","in":"querystring","content":{}}]}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'query and querystring parameters are mutually exclusive') !== false);
        }
    }

    public function testOperationMultipleQuerystring() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"parameters":[{"name":"a","in":"querystring","content":{}},{"name":"b","in":"querystring","content":{}}]}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'querystring parameter MUST NOT appear more than once') !== false);
        }
    }

    public function testResponsesObjectInvalidKeys() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"invalid":{"description":"ok"}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Responses keys must be HTTP status codes, ranges like 2XX, or "default"') !== false);
        }
    }

    public function testResponsesObjectValidKeys() {
        $parsed = \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"},"3XX":{"description":"redirect"},"default":{"description":"default"},"x-custom":{}}}}}}');
        $this->assertEquals('ok', $parsed['paths']['/a']['get']['responses']['200']['description']);
    }

    public function testSecuritySchemeOauth2ImplicitRequiresAuthUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"o":{"type":"oauth2","flows":{"implicit":{"scopes":{}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), "OAuth2 implicit flow requires an 'authorizationUrl' string") !== false);
        }
    }

    public function testSecuritySchemeOauth2PasswordRequiresTokenUrl() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"o":{"type":"oauth2","flows":{"password":{"scopes":{}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), "OAuth2 password flow requires a 'tokenUrl' string") !== false);
        }
    }

    public function testSecuritySchemeOauth2Valid() {
        $parsed = \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"securitySchemes":{"o":{"type":"oauth2","flows":{"implicit":{"authorizationUrl":"http://a","scopes":{}},"password":{"tokenUrl":"http://b","scopes":{}}}}}}}');
        $this->assertEquals('http://a', $parsed['components']['securitySchemes']['o']['flows']['implicit']['authorizationUrl']);
    }
}
