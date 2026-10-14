<?php

/**
 * Tests for standalone PHP function parsing and emission (`\Cdd\Functions`).
 *
 * @package Cdd\Tests\Functions
 */

declare(strict_types=1);

namespace Cdd\Tests\Functions;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Functions\parse` and `\Cdd\Functions\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting a standalone PHP function with docblock.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
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
        $expected = "/**
 * Test function
 */
function my_func(int \$a): int {
    return \$a + 1;
}";
        $this->assertEquals($expected, $emitted);
    }

    /**
     * Tests emitting with missing node/tokens keys.
     *
     * @return void
     */
    public function testEmitWithMissingKeys(): void
    {
        $this->assertEquals('', \Cdd\Functions\emit([]));
    }

    /**
     * Tests parsing syntactically invalid PHP code.
     *
     * @return void
     */
    public function testParseInvalidCode(): void
    {
        $this->assertEquals([], \Cdd\Functions\parse('<?php class { '));
    }

    /**
     * Tests that closures are not extracted as standalone named functions.
     *
     * @return void
     */
    public function testParseAnonymousFunction(): void
    {
        $code = "<?php \$a = function() {};";
        $functions = \Cdd\Functions\parse($code);
        $this->assertEquals(0, count($functions));
    }
}
