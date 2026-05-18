<?php

declare(strict_types=1);

namespace Cdd\Tests\Encoding;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $encodingData = [
            'contentType' => 'application/xml',
            'headers' => [
                'X-Rate-Limit-Limit' => [
                    'description' => 'Limit',
                    'schema' => ['type' => 'integer']
                ]
            ],
            'style' => 'form',
            'explode' => true,
            'allowReserved' => false,
        ];

        $parsed = \Cdd\Encoding\parse($encodingData);
        $this->assertEquals('application/xml', $parsed['contentType']);
        $this->assertTrue(isset($parsed['headers']['X-Rate-Limit-Limit']));

        $emitted = \Cdd\Encoding\emit($parsed);
        $this->assertTrue(strpos($emitted, "'contentType' => 'application/xml'") !== false);
        $this->assertTrue(strpos($emitted, "'X-Rate-Limit-Limit'") !== false);
        $this->assertTrue(strpos($emitted, "'description' => 'Limit'") !== false);
        $this->assertTrue(strpos($emitted, "'style' => 'form'") !== false);
        $this->assertTrue(strpos($emitted, "'explode' => true") !== false);
        $this->assertTrue(strpos($emitted, "'allowReserved' => false") !== false);
    }

    public function testValidateMediaTypeOrReferenceObjectErrors()
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
        ];
        foreach ($tests as $test) {
            $caught = false;
            try {
                \Cdd\Encoding\validateMediaTypeOrReferenceObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        // test success on $ref
        \Cdd\Encoding\validateMediaTypeOrReferenceObject(['$ref' => '#/components/schemas/Item']);

        // test hasItemEncoding coverage
        $caught = false;
        try {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject([
                'itemEncoding' => 'not array'
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Encoding must be an object', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        // encoding objects
        $caught = false;
        try {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject([
                'encoding' => ['foo' => 'not array']
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Encoding must be an object', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Encoding\validateMediaTypeOrReferenceObject([
                'prefixEncoding' => ['not array']
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Encoding must be an object', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        // valid calls
        \Cdd\Encoding\validateMediaTypeOrReferenceObject([
            'itemEncoding' => []
        ]);
        \Cdd\Encoding\validateMediaTypeOrReferenceObject([
            'prefixEncoding' => [[]]
        ]);
        \Cdd\Encoding\validateMediaTypeOrReferenceObject([
            'encoding' => ['foo' => []]
        ]);
    }

    public function testValidateEncodingObjectErrors()
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
        ];
        foreach ($tests as $test) {
            $caught = false;
            try {
                \Cdd\Encoding\validateEncodingObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        $caught = false;
        try {
            \Cdd\Encoding\validateEncodingObject([
                'itemEncoding' => 'not array'
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Encoding must be an object', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Encoding\validateEncodingObject([
                'prefixEncoding' => ['not array']
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Encoding must be an object', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        // valid calls
        \Cdd\Encoding\validateEncodingObject([
            'encoding' => [
                'nested' => []
            ]
        ]);
        \Cdd\Encoding\validateEncodingObject([
            'prefixEncoding' => [
                []
            ]
        ]);
        \Cdd\Encoding\validateEncodingObject([
            'itemEncoding' => []
        ]);
    }
}
