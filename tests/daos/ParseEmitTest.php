<?php

declare(strict_types=1);

namespace Cdd\Tests\Daos;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testEmitDaos()
    {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer']
                ]
            ]
        ];

        $code = \Cdd\Daos\emit($schemas);

        $this->assertStringContainsString('interface UserDaoInterface', $code);
        $this->assertStringContainsString('class StubUserDao implements UserDaoInterface', $code);
        $this->assertStringContainsString('class ConcreteUserDao implements UserDaoInterface', $code);
        $this->assertStringContainsString('class DaoFactory', $code);

        // Check if Stub raises NotImplemented
        $this->assertStringContainsString('throw new \RuntimeException(\'Not implemented\')', $code);

        // Check if Concrete uses Eloquent properly
        $this->assertStringContainsString('User::all()->all()', $code);
        $this->assertStringContainsString('User::find($id)', $code);

        // Check Factory
        $this->assertStringContainsString('new $class()', $code);
    }
}
