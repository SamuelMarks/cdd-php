<?php

declare(strict_types=1);

namespace Cdd\Tests\RequestBodies;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $rb = \Cdd\RequestBodies\parse('User', 'A user object');
        
        $this->assertEquals('A user object', $rb['description']);
        $this->assertTrue($rb['required']);
        $this->assertEquals('#/components/schemas/User', $rb['content']['application/json']['schema']['$ref']);
        
        $emitted = \Cdd\RequestBodies\emit($rb, 'user');
        $this->assertEquals('User $user', $emitted);
    }
}
