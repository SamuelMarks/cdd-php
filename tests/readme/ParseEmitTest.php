<?php

declare(strict_types=1);

namespace Cdd\Tests\Readme;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testEmitReadme()
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
