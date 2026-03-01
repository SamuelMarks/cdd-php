<?php

declare(strict_types=1);

namespace Cdd\Tests\Info;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $info = \Cdd\Info\parse('My API', '2.0.0', 'Does things');
        $this->assertEquals('My API', $info['title']);
        $this->assertEquals('2.0.0', $info['version']);
        $this->assertEquals('Does things', $info['description']);
        
        $emitted = \Cdd\Info\emit($info);
        $this->assertTrue(strpos($emitted, '* My API (v2.0.0)') !== false);
        $this->assertTrue(strpos($emitted, '* Does things') !== false);
    }
}
