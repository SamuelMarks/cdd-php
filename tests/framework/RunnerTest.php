<?php

/**
 * Self-tests for the internal test Runner and TestCase assertions (`tests/framework/Runner.php`).
 *
 * @package Cdd\Tests\Framework
 */

declare(strict_types=1);

namespace Cdd\Tests\Framework;

/**
 * Verifies all TestCase assertion methods, failure modes, expectException, and lifecycle hooks.
 */
class RunnerTest extends TestCase
{
    /**
     * Tests positive and negative branches of all TestCase assertions.
     *
     * @return void
     */
    public function testAllAssertions(): void
    {
        $this->assertEquals(1, 1);
        $this->assertNotEquals(1, 2);
        $this->assertTrue(true);
        $this->assertFalse(false);
        $this->assertNull(null);
        $this->assertNotNull('val');
        $this->assertCount(2, ['a', 'b']);
        $this->assertArrayHasKey('k', ['k' => 'v']);
        $this->assertStringContainsString('sub', 'substring');
        $this->assertIsArray(['a']);
        $this->assertInstanceOf(\stdClass::class, new \stdClass());
        $this->assertEmpty([]);
        $this->assertNotEmpty(['x']);
        $this->assertTrue($this->getAssertionCount() >= 13);

        $this->assertThrows(fn () => $this->assertEquals(1, 2, 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertNotEquals(1, 1, 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertTrue(false, 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertFalse(true, 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertNull(1, 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertNotNull(null, 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertCount(1, [], 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertArrayHasKey('x', [], 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertStringContainsString('x', 'abc', 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertIsArray('str', 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertInstanceOf(\stdClass::class, 'str', 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertEmpty(['a'], 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->assertNotEmpty([], 'msg'), \Exception::class, 'msg:');
        $this->assertThrows(fn () => $this->fail('boom'), \Exception::class, 'boom');
        $this->assertThrows(fn () => $this->assertDoesNotThrow(static function (): void {
            throw new \RuntimeException('inner');
        }, 'msg'), \Exception::class, 'msg:');

        // Test runAll lifecycle with 0 assertions, expectException, and tearDown failure
        $dummy = new class () extends TestCase {
            /**
             * Test method with 0 assertions (should fail).
             *
             * @return void
             */
            public function testZeroAssertions(): void
            {
            }

            /**
             * Test method with expected exception and message.
             *
             * @return void
             * @throws \RuntimeException Always thrown to test expectException.
             */
            public function testExpectedExceptionPass(): void
            {
                $this->expectException(\RuntimeException::class);
                $this->expectExceptionMessage('specific error');
                throw new \RuntimeException('specific error occurred');
            }
        };
        ob_start();
        [$p, $f] = $dummy->runAll();
        ob_end_clean();
        $this->assertEquals(1, $p);
        $this->assertEquals(1, $f);
    }
}
