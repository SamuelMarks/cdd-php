<?php

/**
 * Tests for forbidden fields and mutual exclusivity rules in Headers, Responses, XML, and Paths (`\Cdd\Openapi\parse`).
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests extraneous or conflicting OpenAPI specification properties.
 */
class ParseEmitExtraneousTest extends TestCase
{
    /**
     * Tests errors when Header Object specifies `name`, `in`, `allowEmptyValue`, or non-`simple` style.
     *
     * @return void
     */
    public function testHeaderObjectNoNameInAllowEmptyValue(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"name":"test","schema":{}}}}}');
        }, \RuntimeException::class, 'Header "name" MUST NOT be specified');

        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"in":"query","schema":{}}}}}');
        }, \RuntimeException::class, 'Header "in" MUST NOT be specified');

        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"allowEmptyValue":true,"schema":{}}}}}');
        }, \RuntimeException::class, 'Header "allowEmptyValue" MUST NOT be used');

        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"headers":{"H1":{"style":"matrix","schema":{}}}}}');
        }, \RuntimeException::class, 'MUST be limited to "simple"');
    }

    /**
     * Tests error when Responses Object is empty.
     *
     * @return void
     */
    public function testResponsesObjectNotEmpty(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/p":{"get":{"responses":{}}}}}');
        }, \RuntimeException::class, 'Responses Object MUST contain at least one response code');
    }

    /**
     * Tests errors when XML `nodeType` is combined with `attribute` or `wrapped`.
     *
     * @return void
     */
    public function testXmlObjectMutuallyExclusive(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"S1":{"xml":{"nodeType":"element","attribute":true}}}}}');
        }, \RuntimeException::class, 'XML "attribute" MUST NOT be present if "nodeType" is present');

        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"components":{"schemas":{"S1":{"xml":{"nodeType":"element","wrapped":true}}}}}');
        }, \RuntimeException::class, 'XML "wrapped" MUST NOT be present if "nodeType" is present');
    }

    /**
     * Tests error when a path template contains duplicate variable names.
     *
     * @return void
     */
    public function testDuplicatePathVariables(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Openapi\parse('{"openapi":"3.2.0","info":{"title":"A","version":"1"},"paths":{"/{id}/user/{id}":{}}}');
        }, \RuntimeException::class, 'Path template expressions MUST NOT appear more than once');
    }
}
