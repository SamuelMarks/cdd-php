<?php

declare(strict_types=1);

namespace Cdd\Tests\RequestBodies;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $rb = \Cdd\RequestBodies\parse('User', 'A user object');

        $this->assertEquals('A user object', $rb['description']);
        $this->assertTrue($rb['required']);
        $this->assertEquals('#/components/schemas/User', $rb['content']['application/json']['schema']['$ref']);

        $emitted = \Cdd\RequestBodies\emit($rb, 'user');
        $this->assertEquals('User $user', $emitted);
    }

    public function testValidateRef()
    {
        $ref = ['$ref' => '#/components/requestBodies/User'];
        \Cdd\RequestBodies\validateRequestBodyOrReferenceObject($ref);
        $this->assertEquals('#/components/requestBodies/User', $ref['$ref']);
    }

    public function testValidateDescriptionNotString()
    {
        $this->assertThrows(function () {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject([
                'content' => [],
                'description' => 123
            ]);
        }, \RuntimeException::class, 'Request Body "description" must be a string');
    }

    public function testValidateRequiredNotBool()
    {
        $this->assertThrows(function () {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject([
                'content' => [],
                'required' => 'yes'
            ]);
        }, \RuntimeException::class, 'Request Body "required" must be a boolean');
    }
}
