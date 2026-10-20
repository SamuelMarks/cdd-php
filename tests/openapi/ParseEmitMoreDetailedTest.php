<?php

/**
 * Tests for Response content, Callbacks, Security Requirements, Webhooks, and uniqueness rules (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests uniqueness and structural rules for Operations, Parameters, Webhooks, and Security.
 */
class ParseEmitMoreDetailedTest extends TestCase
{
    /**
     * Tests error when Response `content` is not a map.
     *
     * @return void
     */
    public function testInvalidResponseObjectContent(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"A","content":"invalid"}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Response "content" must be a map');
    }

    /**
     * Tests error when Operation `callbacks` entry is not an object.
     *
     * @return void
     */
    public function testInvalidOperationCallback(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"callbacks":{"ev":"invalid"}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Callback must be an object');
    }

    /**
     * Tests error when Security Requirement scopes is not an array.
     *
     * @return void
     */
    public function testInvalidSecurityRequirementScope(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"security":[{"api_key":"invalid"}],"paths":{}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Requirement scopes must be an array of strings');
    }

    /**
     * Tests error when Security Requirement scope elements are not strings.
     *
     * @return void
     */
    public function testInvalidSecurityRequirementScopeItems(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"security":[{"api_key":[123]}],"paths":{}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Requirement scopes must be an array of strings');
    }

    /**
     * Tests error when an Operation Security Requirement entry is not an object/map.
     *
     * @return void
     */
    public function testInvalidOperationSecurityRequirement(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"security":["invalid"]}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Security Requirement must be an object/map');
    }

    /**
     * Tests error when top-level `webhooks` is not a map.
     *
     * @return void
     */
    public function testInvalidWebhooksObject(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"webhooks":"invalid"}');
        }, \Cdd\Exceptions\CddException::class, 'Field "webhooks" must be a map');
    }

    /**
     * Tests error when a `webhooks` entry is not a Path Item Object.
     *
     * @return void
     */
    public function testInvalidWebhooksPathItem(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"webhooks":{"myHook":"invalid"}}');
        }, \Cdd\Exceptions\CddException::class, 'Path Item must be an object');
    }

    /**
     * Tests error when `operationId` values are duplicated across operations.
     *
     * @return void
     */
    public function testDuplicateOperationIds(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"operationId":"dup","responses":{"200":{"description":"ok"}}}},"/b":{"post":{"operationId":"dup","responses":{"200":{"description":"ok"}}}}}}');
        }, \Cdd\Exceptions\CddException::class, 'MUST be unique among all operations');
    }

    /**
     * Tests error when an Operation parameters list contains duplicates.
     *
     * @return void
     */
    public function testDuplicateOperationParameters(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"get":{"responses":{"200":{"description":"ok"}},"parameters":[{"name":"id","in":"query","schema":{}},{"name":"id","in":"query","schema":{}}]}}}}');
        }, \Cdd\Exceptions\CddException::class, 'Operation parameters list MUST NOT include duplicated parameters');
    }

    /**
     * Tests error when a Path Item parameters list contains duplicates.
     *
     * @return void
     */
    public function testDuplicatePathItemParameters(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/a":{"parameters":[{"name":"id","in":"query","schema":{}},{"name":"id","in":"query","schema":{}}]}}}');
        }, \Cdd\Exceptions\CddException::class, 'Path Item parameters list MUST NOT include duplicated parameters');
    }
}
