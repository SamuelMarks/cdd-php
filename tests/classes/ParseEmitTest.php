<?php

declare(strict_types=1);

namespace Cdd\Tests\Classes;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $code = "<?php

class MyClass {
    public function my_func() {}
}
";
         
        
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));
        $this->assertEquals('MyClass', $classes[0]['name']);
        
        $emitted = \Cdd\Classes\emit($classes[0]);
        $expected = "class MyClass {
    public function my_func() {}
}";
        $this->assertEquals($expected, $emitted);
    }
}
