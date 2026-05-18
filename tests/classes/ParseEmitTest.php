<?php

declare(strict_types=1);

namespace Cdd\Tests\Classes;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $code = "<?php\n\nclass MyClass {\n    public function my_func() {}\n}\n";

        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));
        $this->assertEquals('MyClass', $classes[0]['name']);

        $emitted = \Cdd\Classes\emit($classes[0]);
        $expected = "class MyClass {\n    public function my_func() {}\n}";
        $this->assertEquals($expected, $emitted);
    }

    public function testParseSyntaxError()
    {
        $code = "<?php class {";
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals([], $classes);
    }

    public function testEmitEmpty()
    {
        $this->assertEquals('', \Cdd\Classes\emit([]));
        $this->assertEquals('', \Cdd\Classes\emit(['node' => null]));
    }

    public function testEmitWithComments()
    {
        $code = "<?php\n\n/**\n * Some comment\n */\nclass MyClass2 {}\n";
        $classes = \Cdd\Classes\parse($code);
        $this->assertEquals(1, count($classes));

        $emitted = \Cdd\Classes\emit($classes[0]);
        $expected = "/**\n * Some comment\n */\nclass MyClass2 {}";
        $this->assertEquals($expected, $emitted);
    }

    public function testParseDocblockTypes()
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
            '@link' => 'links'
        ];

        foreach ($tags as $tag => $expectedType) {
            $code = "<?php\n/**\n * {$tag}\n */\nclass MyClass_{$expectedType} {}\n";
            $classes = \Cdd\Classes\parse($code);
            $this->assertEquals(1, count($classes));
            $this->assertEquals($expectedType, $classes[0]['componentType']);
        }
    }
}
