<?php

/**
 * Tests for OpenAPI RequestBody Object parsing, emission, and validation (`\Cdd\RequestBodies`).
 *
 * @package Cdd\Tests\RequestBodies
 */

declare(strict_types=1);

namespace Cdd\Tests\RequestBodies;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\RequestBodies\parse`, `\Cdd\RequestBodies\emit`, and requestBody validation.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting an OpenAPI RequestBody Object.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $rb = \Cdd\RequestBodies\parse('User', 'A user object');

        $this->assertEquals('A user object', $rb['description']);
        $this->assertTrue($rb['required']);
        $this->assertEquals('#/components/schemas/User', $rb['content']['application/json']['schema']['$ref']);

        $emitted = \Cdd\RequestBodies\emit($rb, 'user');
        $this->assertEquals('User $user', $emitted);

        $rbScalar = \Cdd\RequestBodies\parse('string');
        $this->assertEquals('string', $rbScalar['content']['application/json']['schema']['type']);

        $this->assertEquals('User $user', \Cdd\RequestBodies\emit($rb, 'user'));
        $parsedRequiredSig = \Cdd\RequestBodies\parse('User $user', 'A user object');
        $this->assertEquals($rb, $parsedRequiredSig);

        $parsedNullableSig = \Cdd\RequestBodies\parse('?User $body');
        $this->assertFalse($parsedNullableSig['required']);
        $this->assertEquals('#/components/schemas/User', $parsedNullableSig['content']['application/json']['schema']['$ref']);

        $parsedUntypedSig = \Cdd\RequestBodies\parse('$body');
        $this->assertFalse($parsedUntypedSig['required']);
        $this->assertEquals('object', $parsedUntypedSig['content']['application/json']['schema']['type']);

        $parsedNullableType = \Cdd\RequestBodies\parse('?int');
        $this->assertFalse($parsedNullableType['required']);
        $this->assertEquals('integer', $parsedNullableType['content']['application/json']['schema']['type']);
    }

    /**
     * Tests validating a RequestBody Reference Object.
     *
     * @return void
     */
    public function testValidateRef(): void
    {
        $ref = ['$ref' => '#/components/requestBodies/User'];
        $this->assertDoesNotThrow(function () use ($ref): void {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject($ref);
        });
    }

    /**
     * Tests validation error when RequestBody `description` is not a string.
     *
     * @return void
     */
    public function testValidateDescriptionNotString(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject([
                'content' => [],
                'description' => 123,
            ]);
        }, \Cdd\Exceptions\CddException::class, 'Request Body "description" must be a string');
    }

    /**
     * Tests validation error when RequestBody `required` is not a boolean.
     *
     * @return void
     */
    public function testValidateRequiredNotBool(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject([
                'content' => [],
                'required' => 'yes',
            ]);
        }, \Cdd\Exceptions\CddException::class, 'Request Body "required" must be a boolean');
    }
}
