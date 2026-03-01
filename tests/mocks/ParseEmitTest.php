<?php

declare(strict_types=1);

namespace Cdd\Tests\Mocks;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $code = "<?php\n\nreturn ['example1' => ['test' => 123]];\n";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertEquals(123, $parsed['example1']['dataValue']['test']);
        
        $examples = ['example1' => ['dataValue' => ['test' => 123]]];
        $emitted = \Cdd\Mocks\emit($examples);
        $this->assertTrue(strpos($emitted, "'dataValue' =>") !== false);
    }
    public function testParseJSONMocks() {
        $code = "<?php\nreturn [\n    'example1' => ['dataValue' => ['test'=>123]]\n];";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertTrue(isset($parsed['example1']));
        $this->assertEquals(123, $parsed['example1']['dataValue']['test']);
    }

}
