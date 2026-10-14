<?php

/**
 * Tests for DAO interface, stub, concrete implementation, and factory emission (`\Cdd\Daos`).
 *
 * @package Cdd\Tests\Daos
 */

declare(strict_types=1);

namespace Cdd\Tests\Daos;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Daos\emit` and `\Cdd\Daos\emit_modular`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests emitting single-file and modular DAOs and verifying AST syntax validity.
     *
     * @return void
     */
    public function testEmitDaos(): void
    {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
            ],
        ];

        $code = \Cdd\Daos\emit($schemas);

        $this->assertStringContainsString('interface UserDaoInterface', $code);
        $this->assertStringContainsString('class StubUserDao implements UserDaoInterface', $code);
        $this->assertStringContainsString('class ConcreteUserDao implements UserDaoInterface', $code);
        $this->assertStringContainsString('class DaoFactory', $code);
        $this->assertStringContainsString("throw new \\RuntimeException('Not implemented')", $code);
        $this->assertStringContainsString('User::all()->all()', $code);
        $this->assertStringContainsString('User::find($id)', $code);
        $this->assertStringContainsString('new $class()', $code);

        $parser = \Cdd\Classes\create_parser();
        $this->assertNotNull($parser->parse($code));

        $modular = \Cdd\Daos\emit_modular($schemas);
        foreach ($modular as $daoFileCode) {
            $this->assertNotNull($parser->parse($daoFileCode));
        }
    }
}
