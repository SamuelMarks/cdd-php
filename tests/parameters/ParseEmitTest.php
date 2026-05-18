<?php

declare(strict_types=1);

namespace Cdd\Tests\Parameters;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $param = \Cdd\Parameters\parse('id', 'int', 'path');

        $this->assertEquals('id', $param['name']);
        $this->assertEquals('path', $param['in']);
        $this->assertTrue($param['required']);
        $this->assertEquals('integer', $param['schema']['type']);

        $emitted = \Cdd\Parameters\emit($param);
        $this->assertEquals('int $id', $emitted);

        // Test coverage for unknown type (triggers $ref)
        $paramRef = \Cdd\Parameters\parse('userId', 'User', 'query');
        $this->assertEquals('#/components/schemas/User', $paramRef['schema']['$ref']);

        $emittedRef = \Cdd\Parameters\emit($paramRef);
        $this->assertEquals('User $userId', $emittedRef);
    }

    public function testValidateParameterOrReferenceObjectErrors()
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
            ['input' => ['name' => 'foo', 'in' => 'query', 'style' => 123, 'schema' => []], 'error' => 'Parameter "style" must be a string'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'explode' => 123, 'schema' => []], 'error' => 'Parameter "explode" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'allowReserved' => 123, 'schema' => []], 'error' => 'Parameter "allowReserved" must be a boolean'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'schema' => 123], 'error' => 'Parameter "schema" must be an object'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'content' => 123], 'error' => 'Parameter "content" must be a map'],
            ['input' => ['name' => 'foo', 'in' => 'query', 'content' => ['a' => [], 'b' => []]], 'error' => 'Parameter "content" map MUST only contain one entry'],
        ];

        foreach ($tests as $test) {
            $caught = false;
            try {
                \Cdd\Parameters\validateParameterOrReferenceObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        // test $ref
        \Cdd\Parameters\validateParameterOrReferenceObject(['$ref' => '#/components/parameters/Foo']);

        // test valid
        \Cdd\Parameters\validateParameterOrReferenceObject([
            'name' => 'foo',
            'in' => 'query',
            'schema' => []
        ]);

        \Cdd\Parameters\validateParameterOrReferenceObject([
            'name' => 'foo',
            'in' => 'query',
            'content' => ['application/json' => []]
        ]);
    }
}
