<?php

declare(strict_types=1);

namespace Cdd\Tests\Docstrings;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $doc = "/**
 * Test desc
 * @param string \$foo
 * @return int
 */";
        $parsed = \Cdd\Docstrings\parse($doc);
        
        $this->assertEquals("Test desc", $parsed['description']);
        $this->assertEquals("string", $parsed['tags']['param'][0]['type']);
        $this->assertEquals("\$foo", $parsed['tags']['param'][0]['name']);
        
        $emitted = \Cdd\Docstrings\emit($parsed);
        $this->assertTrue(strpos($emitted, '* Test desc') !== false);
        $this->assertTrue(strpos($emitted, '* @param string $foo') !== false);
        $this->assertTrue(strpos($emitted, '* @return int') !== false);
    }
}
