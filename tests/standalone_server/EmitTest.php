<?php

namespace Cdd\Tests\StandaloneServer;

use Cdd\Tests\Framework\TestCase;

use function Cdd\StandaloneServer\emit;

class EmitTest extends TestCase
{
    public function testEmit()
    {
        $code = emit();
        $this->assertTrue(strpos($code, 'class MockRoute') !== false);
        $this->assertTrue(strpos($code, 'php -S localhost:8080') !== false);
    }
}
