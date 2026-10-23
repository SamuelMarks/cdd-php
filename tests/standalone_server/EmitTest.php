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
        $this->assertStringContainsString('Access-Control-Allow-Origin: *', $code);
        $this->assertStringContainsString('/_mock/trigger-webhook/', $code);
        $this->assertStringContainsString('CDD_START_AUTH_SERVER', $code);
        $this->assertStringContainsString('CDD_ENFORCE_AUTH', $code);
        $this->assertStringContainsString('CDD_STRICT_VALIDATION', $code);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($code));

        require_once dirname(__DIR__, 2) . '/bin/check_docs.php';
        $docRes = \audit_php_source_docs($code, 'server.php');
        $this->assertEquals($docRes['total'], $docRes['documented']);
    }
}
