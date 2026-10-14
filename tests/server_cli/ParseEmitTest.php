<?php

/**
 * Tests for `ServerRunner` CLI class emission (`\Cdd\ServerCli\emit`).
 *
 * @package Cdd\Tests\ServerCli
 */

declare(strict_types=1);

namespace Cdd\Tests\ServerCli;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\ServerCli\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests emitting `ServerRunner` with and without namespace and verifying AST syntax validity.
     *
     * @return void
     */
    public function testEmitServerCli(): void
    {
        $code = \Cdd\ServerCli\emit();

        $this->assertStringContainsString('class ServerRunner', $code);
        $this->assertStringContainsString('public function parseArgs(array $argv): void', $code);
        $this->assertStringContainsString('public function run(array $argv): void', $code);
        $this->assertStringContainsString("in_array('--ephemeral', \$argv, true)", $code);
        $this->assertStringContainsString("in_array('--seed', \$argv, true)", $code);
        $this->assertStringContainsString('DatabaseConnection::connect($this->isEphemeral)', $code);
        $this->assertStringContainsString('DatabaseConnection::migrate()', $code);
        $this->assertStringContainsString('$seeder->seed()', $code);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($code));

        $noNsCode = \Cdd\ServerCli\emit('');
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($noNsCode));
    }
}
