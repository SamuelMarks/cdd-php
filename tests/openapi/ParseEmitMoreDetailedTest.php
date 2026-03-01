<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class ParseEmitMoreDetailedTest extends TestCase {
    public function testInvalidResponseObjectContent() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"A","content":"invalid"}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Response "content" must be a map') !== false);
        }
    }

    public function testInvalidOperationCallback() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"callbacks":{"ev":"invalid"}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Callback must be an object') !== false);
        }
    }

    public function testInvalidSecurityRequirementScope() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"security":[{"api_key":"invalid"}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Requirement scopes must be an array of strings') !== false);
        }
    }
    
    public function testInvalidSecurityRequirementScopeItems() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"security":[{"api_key":[123]}],"paths":{}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Requirement scopes must be an array of strings') !== false);
        }
    }

    public function testInvalidOperationSecurityRequirement() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"security":["invalid"]}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Security Requirement must be an object/map') !== false);
        }
    }
    public function testInvalidWebhooksObject() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"webhooks":"invalid"}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Field "webhooks" must be a map') !== false);
        }
    }

    public function testInvalidWebhooksPathItem() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"webhooks":{"myHook":"invalid"}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Path Item must be an object') !== false);
        }
    }

    public function testDuplicateOperationIds() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"operationId":"dup","responses":{"200":{"description":"ok"}}}},"/b":{"post":{"operationId":"dup","responses":{"200":{"description":"ok"}}}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'MUST be unique among all operations') !== false);
        }
    }

    public function testDuplicateOperationParameters() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"parameters":[{"name":"id","in":"query","schema":{}},{"name":"id","in":"query","schema":{}}]}}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Operation parameters list MUST NOT include duplicated parameters') !== false);
        }
    }

    public function testDuplicatePathItemParameters() {
        try {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query","schema":{}},{"name":"id","in":"query","schema":{}}]}}}');
            throw new \Exception('Expected exception not thrown');
        } catch (\RuntimeException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Path Item parameters list MUST NOT include duplicated parameters') !== false);
        }
    }

}
