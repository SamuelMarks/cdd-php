<?php

/**
 * Tests for standalone development server script emission (`\Cdd\StandaloneServer\emit`).
 *
 * @package Cdd\Tests\StandaloneServer
 */

declare(strict_types=1);

namespace Cdd\Tests\StandaloneServer;

use Cdd\Tests\Framework\TestCase;

use function Cdd\StandaloneServer\emit;

/**
 * Tests `\Cdd\StandaloneServer\emit`.
 */
class EmitTest extends TestCase
{
    /**
     * Tests emitting `server.php` and verifying its AST syntax validity.
     *
     * @return void
     */
    public function testEmit(): void
    {
        $code = emit();
        $this->assertTrue(strpos($code, 'class MockRoute') !== false);
        $this->assertTrue(strpos($code, 'php -S 127.0.0.1:8080') !== false);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($code));
    }
}
