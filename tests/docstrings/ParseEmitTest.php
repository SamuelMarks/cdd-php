<?php

/**
 * Tests for PHP docblock parsing and emission (`\Cdd\Docstrings`).
 *
 * @package Cdd\Tests\Docstrings
 */

declare(strict_types=1);

namespace Cdd\Tests\Docstrings;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Docstrings\parse` and `\Cdd\Docstrings\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting description, `@param`, and `@return` docblock tags.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $doc = "/**
 * Test desc
 * @param string \$foo
 * @return int
 */";
        $parsed = \Cdd\Docstrings\parse($doc);

        $this->assertEquals('Test desc', $parsed['description']);
        $this->assertEquals('string', $parsed['tags']['param'][0]['type']);
        $this->assertEquals('$foo', $parsed['tags']['param'][0]['name']);

        $emitted = \Cdd\Docstrings\emit($parsed);
        $this->assertTrue(strpos($emitted, '* Test desc') !== false);
        $this->assertTrue(strpos($emitted, '* @param string $foo') !== false);
        $this->assertTrue(strpos($emitted, '* @return int') !== false);
    }

    /**
     * Tests parsing an empty docblock string.
     *
     * @return void
     */
    public function testParseEmpty(): void
    {
        $this->assertEquals(['description' => '', 'tags' => []], \Cdd\Docstrings\parse(''));
    }

    /**
     * Tests emitting custom docblock tags.
     *
     * @return void
     */
    public function testEmitCustomTags(): void
    {
        $parsed = [
            'description' => '',
            'tags' => [
                'custom' => ['value1', 'value2'],
            ],
        ];
        $emitted = \Cdd\Docstrings\emit($parsed);
        $this->assertTrue(strpos($emitted, '* @custom value1') !== false);
        $this->assertTrue(strpos($emitted, '* @custom value2') !== false);
    }
}
