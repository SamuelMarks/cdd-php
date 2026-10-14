<?php

/**
 * Tests for README Markdown emission (`\Cdd\Readme\emit`).
 *
 * @package Cdd\Tests\Readme
 */

declare(strict_types=1);

namespace Cdd\Tests\Readme;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Readme\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests emitting README documentation for a generated server project.
     *
     * @return void
     */
    public function testEmitReadme(): void
    {
        $code = \Cdd\Readme\emit('MyTest');

        $this->assertStringContainsString('# MyTest Server', $code);
        $this->assertStringContainsString('Stub Mode', $code);
        $this->assertStringContainsString('Production Mode', $code);
        $this->assertStringContainsString('Sandbox Mode', $code);
        $this->assertStringContainsString('Full Mock Mode', $code);
        $this->assertStringContainsString('--ephemeral --seed', $code);
    }
}
