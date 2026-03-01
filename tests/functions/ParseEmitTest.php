<?php

declare(strict_types=1);

namespace Cdd\Tests\Functions;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $code = "<?php

/**
 * Test function
 */
function my_func(int \$a): int {
    return \$a + 1;
}
";
         
        
        $functions = \Cdd\Functions\parse($code);
        $this->assertEquals(1, count($functions));
        
        $this->assertEquals('my_func', $functions[0]['name']);
        
        $emitted = \Cdd\Functions\emit($functions[0]);
                                $expected = '/**
 * Test function
 */
function my_func(int $a): int {
    return $a + 1;
}';
        $this->assertEquals($expected, $emitted);
    }
}
