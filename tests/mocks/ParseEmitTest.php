<?php

declare(strict_types=1);

namespace Cdd\Tests\Mocks;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $code = "<?php\n\nreturn ['example1' => ['test' => 123]];\n";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertEquals(123, $parsed['example1']['dataValue']['test']);

        $examples = ['example1' => ['dataValue' => ['test' => 123]]];
        $emitted = \Cdd\Mocks\emit($examples);
        $this->assertTrue(strpos($emitted, "'dataValue' =>") !== false);
    }

    public function testEmitModular()
    {
        $examples = ['example1' => ['dataValue' => ['test' => 123]]];
        $files = \Cdd\Mocks\emit_modular($examples);

        $this->assertTrue(isset($files['example1Mock.php']));
        $this->assertStringContainsString("'dataValue' =>", $files['example1Mock.php']);
    }
    public function testParseJSONMocks()
    {
        $code = "<?php\nreturn [\n    'example1' => ['dataValue' => ['test'=>123]]\n];";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertTrue(isset($parsed['example1']));
        $this->assertEquals(123, $parsed['example1']['dataValue']['test']);
    }


    public function testParseCatch()
    {
        $code = "<?php throw new \Exception('test');";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertEquals([], $parsed);

        $invalidSyntax = "<?php return [;";
        $this->assertEquals([], \Cdd\Mocks\parse($invalidSyntax));

        $richMocks = "<?php return [\n"
            . "    'ex1' => [\n"
            . "        'negInt' => -42,\n"
            . "        'negFloat' => -3.14,\n"
            . "        'flagTrue' => true,\n"
            . "        'flagFalse' => false,\n"
            . "        'flagNull' => null,\n"
            . "        'list' => ['a', 'b'],\n"
            . "    ]\n"
            . "];";
        $parsedRich = \Cdd\Mocks\parse($richMocks);
        $this->assertEquals(-42, $parsedRich['ex1']['dataValue']['negInt']);
        $this->assertEquals(-3.14, $parsedRich['ex1']['dataValue']['negFloat']);
        $this->assertTrue($parsedRich['ex1']['dataValue']['flagTrue']);
        $this->assertFalse($parsedRich['ex1']['dataValue']['flagFalse']);
        $this->assertNull($parsedRich['ex1']['dataValue']['flagNull']);
        $this->assertEquals(['a', 'b'], $parsedRich['ex1']['dataValue']['list']);
        $this->assertNull(\Cdd\Mocks\evaluateAstNode(null));
    }

    public function testValidateExampleOrReferenceObject()
    {
        $tests = [
            ['input' => 'not array', 'error' => 'Example must be an object'],
            ['input' => ['summary' => 123], 'error' => 'Example "summary" must be a string'],
            ['input' => ['description' => 123], 'error' => 'Example "description" must be a string'],
            ['input' => ['dataValue' => 1, 'value' => 2], 'error' => 'Example cannot contain both "dataValue" and "value"'],
            ['input' => ['serializedValue' => 'a', 'value' => 1], 'error' => 'Example cannot contain "serializedValue" with "value" or "externalValue"'],
            ['input' => ['externalValue' => 123], 'error' => 'Example "externalValue" must be a string (URI)'],
            ['input' => ['serializedValue' => 123], 'error' => 'Example "serializedValue" must be a string'],
        ];

        foreach ($tests as $test) {
            $caught = false;
            try {
                \Cdd\Mocks\validateExampleOrReferenceObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        // test valid reference object
        \Cdd\Mocks\validateExampleOrReferenceObject(['$ref' => '#/components/examples/Test']);
    }
}
