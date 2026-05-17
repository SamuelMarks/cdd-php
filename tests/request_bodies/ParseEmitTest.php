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
        \Cdd\RequestBodies\validateRequestBodyOrReferenceObject(['$ref' => '#/components/requestBodies/User']);
        $this->assertTrue(true); // just testing it doesn't throw
    }

    public function testValidateDescriptionNotString()
    {
        try {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject([
                'content' => [],
                'description' => 123
            ]);
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Request Body "description" must be a string', $e->getMessage());
        }
    }

    public function testValidateRequiredNotBool()
    {
        try {
            \Cdd\RequestBodies\validateRequestBodyOrReferenceObject([
                'content' => [],
                'required' => 'yes'
            ]);
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Request Body "required" must be a boolean', $e->getMessage());
        }
    }
}
