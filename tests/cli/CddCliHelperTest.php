<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class CddCliHelperTest extends TestCase
{
    public function testHelpers()
    {
        ob_start();
        CddCli::generate_from_openapi([]);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        $config = new \Cdd\Cli\CddConfig();
        $config->target = 'to_sdk';
        ob_start();
        CddCli::generate_from_openapi($config);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        ob_start();
        CddCli::generate_to_openapi([]);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        $config2 = new \Cdd\Cli\CddConfig();
        ob_start();
        CddCli::generate_to_openapi($config2);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        ob_start();
        $this->assertEquals(1, CddCli::generate_docs_json([]));
        $out = ob_get_clean();

        $config3 = new \Cdd\Cli\CddConfig();
        ob_start();
        $this->assertEquals(1, CddCli::generate_docs_json($config3));
        $out = ob_get_clean();

        // Call the helper directly with a short timeout
        ob_start();
        $this->assertEquals(0, CddCli::serve_json_rpc(['--port', '0', '--timeout', '0.01']));
        ob_get_clean();

        $_ENV['CDD_PORT'] = '0';
        $_ENV['CDD_TIMEOUT'] = '0.01';
        $config4 = new \Cdd\Cli\CddConfig();
        ob_start();
        $this->assertEquals(0, CddCli::serve_json_rpc($config4));
        ob_get_clean();
        unset($_ENV['CDD_PORT'], $_ENV['CDD_TIMEOUT']);

        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'serve_json_rpc', '-p', '0', '--timeout', '0.01']));
        ob_get_clean();

        ob_start();
        CddCli::run(['cdd-php', '-v']);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, '0.0.3') !== false);

        ob_start();
        CddCli::run(['cdd-php', '-h']);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Usage:') !== false);

        // Subcommand help texts
        $subcommands = ['from_openapi', 'to_openapi', 'to_docs_json', 'serve_json_rpc', 'sync', 'mcp'];
        foreach ($subcommands as $cmd) {
            ob_start();
            CddCli::run(['cdd-php', $cmd, '--help']);
            $out = ob_get_clean();
            $this->assertTrue(strpos($out, 'Usage:') !== false || strpos($out, 'Run the Model') !== false);
        }

        // Env var injection
        $_ENV['CDD_TEST_ARG'] = 'val';
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'unknown_command']));
        $out = ob_get_clean();
        unset($_ENV['CDD_TEST_ARG']);

        // App class
        $this->assertEquals(0, \Cdd\Cli\Application::serveJsonRpc([], 0));
    }
}
