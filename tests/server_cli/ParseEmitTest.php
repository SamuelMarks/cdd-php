<?php

declare(strict_types=1);

namespace Cdd\Tests\ServerCli;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testEmitServerCli()
    {
        $code = \Cdd\ServerCli\emit();

        $this->assertStringContainsString('class ServerRunner', $code);
        $this->assertStringContainsString('public function parseArgs(array $argv): void', $code);
        $this->assertStringContainsString('public function run(array $argv): void', $code);
        $this->assertStringContainsString('in_array(\'--ephemeral\', $argv, true)', $code);
        $this->assertStringContainsString('in_array(\'--seed\', $argv, true)', $code);
        $this->assertStringContainsString('DatabaseConnection::connect($this->isEphemeral)', $code);
        $this->assertStringContainsString('DatabaseConnection::migrate()', $code);
        $this->assertStringContainsString('$seeder->seed()', $code);
    }
}
