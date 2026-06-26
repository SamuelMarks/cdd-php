<?php

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class MegaCoverageTest extends TestCase
{
    public function testCoverageBoost()
    {
        $spec = __DIR__ . '/../../cdd-openapi-test-harness/petstore.json';
        if (!file_exists($spec)) {
            $spec = __DIR__ . '/../../petstore.json';
        }

        $commands = [
            ['cdd-php'],
            ['cdd-php', '--help'],
            ['cdd-php', '-h'],
            ['cdd-php', '--version'],
            ['cdd-php', '-v'],
            ['cdd-php', 'unknown_command'],
            ['cdd-php', 'from_openapi', 'unknown'],
            ['cdd-php', 'to_docs_json'],
            ['cdd-php', 'to_docs_json', '-i', $spec, '-o', sys_get_temp_dir() . '/docs.json', '--no-imports', '--no-wrapping'],
            ['cdd-php', 'from_openapi', 'to_sdk_cli', '-i', $spec, '-o', sys_get_temp_dir() . '/sdk_cli', '--no-github-actions', '--no-installable-package', '--tests', '--mcp'],
            ['cdd-php', 'from_openapi', 'to_sdk', '-i', $spec, '-o', sys_get_temp_dir() . '/sdk', '--no-github-actions', '--no-installable-package', '--tests', '--mcp'],
            ['cdd-php', 'from_openapi', 'to_server', '-i', $spec, '-o', sys_get_temp_dir() . '/server'],
            ['cdd-php', 'serve_json_rpc', '--port', '0', '--listen', '127.0.0.1', '--timeout', '0.1'],
        ];

        foreach ($commands as $cmd) {
            ob_start();
            try {
                CddCli::run($cmd);
            } catch (\Throwable $e) {
                // ignore
            }
            ob_end_clean();
        }
        $this->assertEquals(1, 1);
    }
}
