<?php

/**
 * Tests for OpenAPI Example / Mock parsing, emission, and validation (`\Cdd\Mocks`).
 *
 * @package Cdd\Tests\Mocks
 */

declare(strict_types=1);

namespace Cdd\Tests\Mocks;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Mocks\parse`, `\Cdd\Mocks\emit`, `\Cdd\Mocks\emit_modular`, and example validation.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting PHP mock arrays.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $code = "<?php

return ['example1' => ['test' => 123]];
";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertEquals(123, $parsed['example1']['dataValue']['test']);

        $examples = ['example1' => ['dataValue' => ['test' => 123]]];
        $emitted = \Cdd\Mocks\emit($examples);
        $this->assertTrue(strpos($emitted, "'dataValue' =>") !== false);
    }

    /**
     * Tests emitting modular mock files.
     *
     * @return void
     */
    public function testEmitModular(): void
    {
        $examples = ['example1' => ['dataValue' => ['test' => 123]]];
        $files = \Cdd\Mocks\emit_modular($examples);

        $this->assertTrue(isset($files['example1Mock.php']));
        $this->assertStringContainsString("'dataValue' =>", $files['example1Mock.php']);
    }

    /**
     * Tests parsing mock files that already wrap data inside `dataValue`.
     *
     * @return void
     */
    public function testParseJSONMocks(): void
    {
        $code = "<?php
return [
    'example1' => ['dataValue' => ['test'=>123]]
];";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertTrue(isset($parsed['example1']));
        $this->assertEquals(123, $parsed['example1']['dataValue']['test']);
    }

    /**
     * Tests AST evaluation of scalar, unary, boolean, null, and list nodes in mock files.
     *
     * @return void
     */
    public function testParseCatch(): void
    {
        $code = "<?php throw new \Exception('test');";
        $parsed = \Cdd\Mocks\parse($code);
        $this->assertEquals([], $parsed);

        $invalidSyntax = '<?php return [;';
        $this->assertEquals([], \Cdd\Mocks\parse($invalidSyntax));

        $richMocks = "<?php return [
"
            . "    'ex1' => [
"
            . "        'negInt' => -42,
"
            . "        'negFloat' => -3.14,
"
            . "        'flagTrue' => true,
"
            . "        'flagFalse' => false,
"
            . "        'flagNull' => null,
"
            . "        'list' => ['a', 'b'],
"
            . "    ]
"
            . "];";
        $parsedRich = \Cdd\Mocks\parse($richMocks);
        $this->assertEquals(-42, $parsedRich['ex1']['dataValue']['negInt']);
        $this->assertEquals(-3.14, $parsedRich['ex1']['dataValue']['negFloat']);
        $this->assertTrue($parsedRich['ex1']['dataValue']['flagTrue']);
        $this->assertFalse($parsedRich['ex1']['dataValue']['flagFalse']);
        $this->assertNull($parsedRich['ex1']['dataValue']['flagNull']);
        $this->assertEquals(['a', 'b'], $parsedRich['ex1']['dataValue']['list']);
        $this->assertNull(\Cdd\Classes\evaluate_ast_node(null));
    }

    /**
     * Tests validation errors and valid shapes for Example or Reference Objects.
     *
     * @return void
     */
    public function testValidateExampleOrReferenceObject(): void
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
            $this->assertThrows(function () use ($test): void {
                \Cdd\Mocks\validateExampleOrReferenceObject($test['input']);
            }, \Cdd\Exceptions\CddException::class, $test['error']);
        }

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Mocks\validateExampleOrReferenceObject(['$ref' => '#/components/examples/Test']);
        });
    }
}
