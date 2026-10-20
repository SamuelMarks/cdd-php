<?php

/**
 * Tests for Parameter Object parsing, emission, and validation.
 *
 * @package Cdd\Tests\Parameters
 */

declare(strict_types=1);

namespace Cdd\Tests\Parameters;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Parameters\parse`, `\Cdd\Parameters\emit`, and parameter validators.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting OpenAPI Parameter Objects.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $param = \Cdd\Parameters\parse('id', 'int', 'path');

        $this->assertEquals('id', $param['name']);
        $this->assertEquals('path', $param['in']);
        $this->assertTrue($param['required']);
        $this->assertEquals('integer', $param['schema']['type']);

        $emitted = \Cdd\Parameters\emit($param);
        $this->assertEquals('int $id', $emitted);

        $paramRef = \Cdd\Parameters\parse('userId', 'User', 'query', false);
        $this->assertEquals('#/components/schemas/User', $paramRef['schema']['$ref']);

        $emittedRef = \Cdd\Parameters\emit($paramRef);
        $this->assertEquals('?User $userId', $emittedRef);

        $paramNoSchema = ['name' => 'noType', 'in' => 'query'];
        $emittedNoSchema = \Cdd\Parameters\emit($paramNoSchema);
        $this->assertEquals('$noType', $emittedNoSchema);

        $this->assertEquals('param_123', \Cdd\Parameters\sanitize_var_name('123'));
        $this->assertEquals('X_Request_ID', \Cdd\Parameters\sanitize_var_name('X-Request-ID'));

        // Roundtrip emitted PHP parameter signatures through Cdd\Parameters\parse
        $parsedFromSig = \Cdd\Parameters\parse($emitted, null, 'path', true, 'User identifier');
        $this->assertEquals('id', $parsedFromSig['name']);
        $this->assertEquals('integer', $parsedFromSig['schema']['type']);
        $this->assertEquals('User identifier', $parsedFromSig['description']);

        $parsedNullableSig = \Cdd\Parameters\parse($emittedRef, null, 'query');
        $this->assertEquals('userId', $parsedNullableSig['name']);
        $this->assertFalse($parsedNullableSig['required']);
        $this->assertEquals('#/components/schemas/User', $parsedNullableSig['schema']['$ref']);

        $parsedUntypedSig = \Cdd\Parameters\parse($emittedNoSchema, null, 'query');
        $this->assertEquals('noType', $parsedUntypedSig['name']);
        $this->assertFalse($parsedUntypedSig['required']);
        $this->assertEquals('string', $parsedUntypedSig['schema']['type']);
    }

    /**
     * Tests validation and emission of optional Parameter Object fields.
     *
     * @return void
     */
    public function testValidateParameterObjectValidOptionals(): void
    {
        $param1 = [
            'name' => 'foo',
            'in' => 'query',
            'style' => 'form',
            'explode' => true,
            'allowReserved' => true,
            'schema' => ['type' => 'string'],
        ];
        $this->assertDoesNotThrow(function () use ($param1): void {
            \Cdd\Parameters\validateParameterOrReferenceObject($param1);
        });
        $this->assertEquals('?string $foo', \Cdd\Parameters\emit($param1));

        $param2 = [
            'name' => 'foo',
            'in' => 'query',
            'content' => [
                'application/json' => [
                    'schema' => ['type' => 'string'],
                ],
            ],
        ];
        $this->assertDoesNotThrow(function () use ($param2): void {
            \Cdd\Parameters\validateParameterOrReferenceObject($param2);
        });
        $this->assertEquals('$foo', \Cdd\Parameters\emit($param2));
    }

    /**
     * Tests validation errors and valid shapes for Parameter or Reference Objects.
     *
     * @return void
     */
    public function testValidateParameterOrReferenceObjectErrors(): void
    {
        $tests = [
            ['input' => 'string', 'error' => 'Parameter must be an object'],
            ['input' => ['name' => 123, 'in' => 'query', 'schema' => []], 'error' => 'Parameter must contain a "name" string'],
            ['input' => ['name' => 'foo', 'in' => 123, 'schema' => []], 'error' => 'Parameter must contain an "in" string'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'description' => 123, 'schema' => []], 'error' => 'Parameter "description" must be a string'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'required' => 123, 'schema' => []], 'error' => 'Parameter "required" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'deprecated' => 123, 'schema' => []], 'error' => 'Parameter "deprecated" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'allowEmptyValue' => 123, 'schema' => []], 'error' => 'Parameter "allowEmptyValue" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'header', 'allowEmptyValue' => true, 'schema' => []], 'error' => 'Parameter "allowEmptyValue" is only allowed for in: query'],
            ['input' => ['name' => 'foo', 'in' => 'path', 'required' => false, 'schema' => []], 'error' => 'Parameter with in: path MUST have required: true'],
            ['input' => ['name' => 'foo', 'in' => 'query'], 'error' => 'Parameter must contain either "schema" or "content"'],
            ['input' => ['name' => 'foo', 'in' => 'querystring', 'schema' => []], 'error' => 'Fields schema, style, explode, allowReserved MUST NOT be used with in: querystring'],
            ['input' => ['name' => 'foo', 'in' => 'querystring', 'schema' => [], 'explode' => true], 'error' => 'Fields schema, style, explode, allowReserved MUST NOT be used with in: querystring'],
            ['input' => ['name' => 'foo', 'in' => 'querystring', 'schema' => [], 'style' => 'form'], 'error' => 'Fields schema, style, explode, allowReserved MUST NOT be used with in: querystring'],
            ['input' => ['name' => 'foo', 'in' => 'querystring', 'schema' => [], 'allowReserved' => true], 'error' => 'Fields schema, style, explode, allowReserved MUST NOT be used with in: querystring'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'style' => 123, 'schema' => []], 'error' => 'Parameter "style" must be a string'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'explode' => 123, 'schema' => []], 'error' => 'Parameter "explode" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'allowReserved' => 123, 'schema' => []], 'error' => 'Parameter "allowReserved" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'schema' => 123], 'error' => 'Parameter "schema" must be an object'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'content' => 123], 'error' => 'Parameter "content" must be a map'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'content' => ['a' => [], 'b' => []]], 'error' => 'Parameter "content" map MUST only contain one entry'],
        ];

        foreach ($tests as $test) {
            $this->assertThrows(function () use ($test): void {
                \Cdd\Parameters\validateParameterOrReferenceObject($test['input']);
            }, \Cdd\Exceptions\CddException::class, $test['error']);
        }

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Parameters\validateParameterOrReferenceObject(['$ref' => '#/components/parameters/Foo']);
            \Cdd\Parameters\validateParameterOrReferenceObject(['name' => 'foo', 'in' => 'query', 'schema' => []]);
            \Cdd\Parameters\validateParameterOrReferenceObject(['name' => 'foo', 'in' => 'query', 'content' => ['application/json' => []]]);
        });
    }
}
