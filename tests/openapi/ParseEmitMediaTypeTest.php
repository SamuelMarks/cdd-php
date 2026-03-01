<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitMediaTypeTest extends TestCase {
    public function testMediaTypeExampleAndExamples() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"example":1,"examples":{}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Media Type cannot contain both "example" and "examples"') !== false);
        }
    }

    public function testMediaTypeEncodingAndPrefixEncoding() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"encoding":{},"prefixEncoding":[]}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"') !== false);
        }
    }

    public function testMediaTypeEncodingInvalidType() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"encoding":"invalid"}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Media Type "encoding" must be a map') !== false);
        }
    }

    public function testEncodingObjectInvalidContentType() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"requestBody":{"content":{"app/json":{"encoding":{"prop":{"contentType":123}}}}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Encoding "contentType" must be a string') !== false);
        }
    }
}
