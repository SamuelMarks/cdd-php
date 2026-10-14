<?php

/**
 * Tests for PHP class parsing, token extraction, and emission.
 *
 * @package Cdd\Tests\Classes
 */

declare(strict_types=1);

namespace Cdd\Tests\Classes;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Classes\parse` and `\Cdd\Classes\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting a standard PHP class.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
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

    /**
     * Tests parsing syntactically invalid PHP code.
     *
     * @return void
     */
    public function testParseSyntaxError(): void
    {
        $code = '<?php class {';
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals([], $classes);
    }

    /**
     * Tests emitting from an empty or incomplete class info array.
     *
     * @return void
     */
    public function testEmitEmpty(): void
    {
        $this->assertEquals('', \Cdd\Classes\emit([]));
        $this->assertEquals('', \Cdd\Classes\emit(['node' => null]));
    }

    /**
     * Tests emitting a class while preserving its leading docblock comment.
     *
     * @return void
     */
    public function testEmitWithComments(): void
    {
        $code = "<?php

/**
 * Some comment
 */
class MyClass2 {}
";
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));

        $emitted = \Cdd\Classes\emit($classes[0]);
        $expected = "/**
 * Some comment
 */
class MyClass2 {}";
        $this->assertEquals($expected, $emitted);
    }

    /**
     * Tests componentType detection from class docblock tags.
     *
     * @return void
     */
    public function testParseDocblockTypes(): void
    {
        $tags = [
            '@mediaType' => 'mediaTypes',
            '@parameter' => 'parameters',
            '@response' => 'responses',
            '@requestBody' => 'requestBodies',
            '@header' => 'headers',
            '@securityScheme' => 'securitySchemes',
            '@pathItem' => 'pathItems',
            '@callback' => 'callbacks',
            '@link' => 'links',
        ];

        foreach ($tags as $tag => $expectedType) {
            $code = "<?php
/**
 * {$tag}
 */
class MyClass_{$expectedType} {}
";
            $classes = \Cdd\Classes\parse($code);
            $this->assertEquals(1, count($classes));
            $this->assertEquals($expectedType, $classes[0]['componentType']);
        }
    }

    /**
     * Tests parsing an anonymous class expression.
     *
     * @return void
     */
    public function testParseAnonymousClass(): void
    {
        $code = "<?php
\$a = new class {};";
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));
        $this->assertEquals('', $classes[0]['name']);
    }
}
