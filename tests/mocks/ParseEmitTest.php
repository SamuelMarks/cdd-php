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
