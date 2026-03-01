<?php

declare(strict_types=1);

namespace Cdd\Tests\Parameters;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $param = \Cdd\Parameters\parse('id', 'int', 'path');
        
        $this->assertEquals('id', $param['name']);
        $this->assertEquals('path', $param['in']);
        $this->assertTrue($param['required']);
        $this->assertEquals('integer', $param['schema']['type']);
        
        $emitted = \Cdd\Parameters\emit($param);
        $this->assertEquals('int $id', $emitted);
    }
}
