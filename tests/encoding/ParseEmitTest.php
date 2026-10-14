<?php

/**
 * Tests for Encoding and Media Type Object parsing, emission, and validation.
 *
 * @package Cdd\Tests\Encoding
 */

declare(strict_types=1);

namespace Cdd\Tests\Encoding;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Encoding\parse`, `\Cdd\Encoding\emit`, and encoding/mediaType validators.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting an OpenAPI Encoding Object.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $encodingData = [
            'contentType' => 'application/xml',
            'headers' => [
                'X-Rate-Limit-Limit' => [
                    'description' => 'Limit',
                    'schema' => ['type' => 'integer'],
                ],
            ],
            'style' => 'form',
            'explode' => false,
            'allowReserved' => true,
        ];

        $parsed = \Cdd\Encoding\parse($encodingData);
        $this->assertEquals('application/xml', $parsed['contentType']);
        $this->assertTrue(isset($parsed['headers']['X-Rate-Limit-Limit']));

        $emitted = \Cdd\Encoding\emit($parsed);
        $this->assertTrue(strpos($emitted, "'contentType' => 'application/xml'") !== false);
        $this->assertTrue(strpos($emitted, "'X-Rate-Limit-Limit'") !== false);
        $this->assertTrue(strpos($emitted, "'description' => 'Limit'") !== false);
        $this->assertTrue(strpos($emitted, "'style' => 'form'") !== false);
        $this->assertTrue(strpos($emitted, "'explode' => false") !== false);
        $this->assertTrue(strpos($emitted, "'allowReserved' => true") !== false);
    }

    /**
     * Tests emitting Encoding Object boolean flags.
     *
     * @return void
     */
    public function testEmitWithTrueBooleans(): void
    {
        $encodingData = [
            'explode' => true,
            'allowReserved' => false,
        ];
        $emitted = \Cdd\Encoding\emit($encodingData);
        $this->assertTrue(strpos($emitted, "'explode' => true") !== false);
        $this->assertTrue(strpos($emitted, "'allowReserved' => false") !== false);
    }

    /**
     * Tests validation errors and valid shapes for MediaType or Reference Objects.
     *
     * @return void
     */
    public function testValidateMediaTypeOrReferenceObjectErrors(): void
    {
        $tests = [
            ['input' => 'string', 'error' => 'Media Type must be an object'],
            ['input' => ['schema' => 123], 'error' => 'Media Type "schema" must be an object'],
            ['input' => ['itemSchema' => 123], 'error' => 'Media Type "itemSchema" must be an object'],
            ['input' => ['examples' => 123], 'error' => 'Media Type "examples" must be a map'],
            ['input' => ['example' => 1, 'examples' => []], 'error' => 'Media Type cannot contain both "example" and "examples"'],
            ['input' => ['encoding' => 123], 'error' => 'Media Type "encoding" must be a map'],
            ['input' => ['prefixEncoding' => 123], 'error' => 'Media Type "prefixEncoding" must be an array'],
            ['input' => ['encoding' => [], 'prefixEncoding' => []], 'error' => 'Media Type "encoding" cannot be present with "prefixEncoding" or "itemEncoding"'],
            ['input' => ['itemEncoding' => 'not array'], 'error' => 'Encoding must be an object'],
            ['input' => ['encoding' => ['foo' => 'not array']], 'error' => 'Encoding must be an object'],
            ['input' => ['prefixEncoding' => ['not array']], 'error' => 'Encoding must be an object'],
        ];
        foreach ($tests as $test) {
            $this->assertThrows(function () use ($test): void {
                \Cdd\Encoding\validateMediaTypeOrReferenceObject($test['input']);
            }, \RuntimeException::class, $test['error']);
        }

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject(['$ref' => '#/components/schemas/Item']);
            \Cdd\Encoding\validateMediaTypeOrReferenceObject(['itemEncoding' => []]);
            \Cdd\Encoding\validateMediaTypeOrReferenceObject(['prefixEncoding' => [[]]]);
            \Cdd\Encoding\validateMediaTypeOrReferenceObject(['encoding' => ['foo' => []]]);
        });
    }

    /**
     * Tests validation errors and valid shapes for Encoding Objects.
     *
     * @return void
     */
    public function testValidateEncodingObjectErrors(): void
    {
        $tests = [
            ['input' => 'string', 'error' => 'Encoding must be an object'],
            ['input' => ['contentType' => 123], 'error' => 'Encoding "contentType" must be a string'],
            ['input' => ['headers' => 123], 'error' => 'Encoding "headers" must be a map'],
            ['input' => ['style' => 123], 'error' => 'Encoding "style" must be a string'],
            ['input' => ['explode' => 123], 'error' => 'Encoding "explode" must be a boolean'],
            ['input' => ['allowReserved' => 123], 'error' => 'Encoding "allowReserved" must be a boolean'],
            ['input' => ['encoding' => ['x' => 'not array']], 'error' => 'Encoding must be an object'],
            ['input' => ['encoding' => 123], 'error' => 'Encoding "encoding" must be a map'],
            ['input' => ['prefixEncoding' => 123], 'error' => 'Encoding "prefixEncoding" must be an array'],
            ['input' => ['encoding' => [], 'prefixEncoding' => []], 'error' => 'Encoding "encoding" cannot be present with "prefixEncoding" or "itemEncoding"'],
            ['input' => ['itemEncoding' => 'not array'], 'error' => 'Encoding must be an object'],
            ['input' => ['prefixEncoding' => ['not array']], 'error' => 'Encoding must be an object'],
        ];
        foreach ($tests as $test) {
            $this->assertThrows(function () use ($test): void {
                \Cdd\Encoding\validateEncodingObject($test['input']);
            }, \RuntimeException::class, $test['error']);
        }

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Encoding\validateEncodingObject(['encoding' => ['nested' => []]]);
            \Cdd\Encoding\validateEncodingObject(['prefixEncoding' => [[]]]);
            \Cdd\Encoding\validateEncodingObject(['itemEncoding' => []]);
        });
    }
}
