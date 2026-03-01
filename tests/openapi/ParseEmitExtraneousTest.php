<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitExtraneousTest extends TestCase {

    public function testHeaderObjectNoNameInAllowEmptyValue() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"name":"test","schema":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Header "name" MUST NOT be specified') !== false);
        }

        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"in":"query","schema":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Header "in" MUST NOT be specified') !== false);
        }

        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"allowEmptyValue":true,"schema":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Header "allowEmptyValue" MUST NOT be used') !== false);
        }

        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"style":"matrix","schema":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'MUST be limited to "simple"') !== false);
        }
    }

    public function testResponsesObjectNotEmpty() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/p":{"get":{"responses":{}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Responses Object MUST contain at least one response code') !== false);
        }
    }

    public function testXmlObjectMutuallyExclusive() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"S1":{"xml":{"nodeType":"element","attribute":true}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'XML "attribute" MUST NOT be present if "nodeType" is present') !== false);
        }

        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"S1":{"xml":{"nodeType":"element","wrapped":true}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'XML "wrapped" MUST NOT be present if "nodeType" is present') !== false);
        }
    }

    public function testDuplicatePathVariables() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/{id}/user/{id}":{}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Path template expressions MUST NOT appear more than once') !== false);
        }
    }
}
