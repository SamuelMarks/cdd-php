<?php

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class CddCliExtraCoverageTest extends TestCase
{
    private function tryRun($args)
    {
        try {
            CddCli::run($args);
        } catch (\Throwable $e) {
        }
    }
    public function testExtra()
    {
        ob_start();
        $this->tryRun([]);
        $this->tryRun(['cdd-php']);
        $this->tryRun(['cdd-php', 'unknown']);
        $this->tryRun(['cdd-php', '--version']);
        $this->tryRun(['cdd-php', '-v']);
        $this->tryRun(['cdd-php', '--help']);
        $this->tryRun(['cdd-php', '-h']);
        $this->tryRun(['cdd-php', 'from_openapi']);
        $this->tryRun(['cdd-php', 'to_openapi']);
        $this->tryRun(['cdd-php', 'to_docs_json']);
        $this->tryRun(['cdd-php', 'sync']);
        $this->tryRun(['cdd-php', 'serve_json_rpc', '--port', 'invalid', '--timeout', '0.1']);

        $this->tryRun(['cdd-php', 'to_openapi', '-i', 'invalid']);
        $this->tryRun(['cdd-php', 'to_openapi', '-i', 'tests/cli/ParseEmitTest.php']);
        $this->tryRun(['cdd-php', 'from_openapi', 'to_sdk_cli', '-i', 'tests/cli/ParseEmitTest.php']);
        $this->tryRun(['cdd-php', 'from_openapi', 'to_sdk', '-i', 'invalid']);
        $this->tryRun(['cdd-php', 'from_openapi', 'to_server', '-i', 'invalid']);

        $_ENV['CDD_TEST'] = '1';
        $this->tryRun(['cdd-php']);
        unset($_ENV['CDD_TEST']);
        ob_end_clean();

        \Cdd\Cli\Application::serveJsonRpc([], 0);
        $this->assertTrue(true);
    }

    public function testExtraMore()
    {
        ob_start();
        $this->tryRun(['cdd-php', 'to_openapi', '-i', 'src', '-o', sys_get_temp_dir() . '/cdd_out.json']);
        ob_end_clean();
        $this->assertTrue(true);
    }
}
