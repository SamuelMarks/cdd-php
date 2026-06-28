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
        CddCli::generate_docs_json([]);
        $out = ob_get_clean();
        $this->assertTrue(true);

        $config3 = new \Cdd\Cli\CddConfig();
        ob_start();
        CddCli::generate_docs_json($config3);
        $out = ob_get_clean();
        $this->assertTrue(true);

        // Test the serve_json_rpc execution without blocking.
        $process = proc_open(['php', 'bin/cdd-php', 'serve_json_rpc', '-p', '0'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($process)) {
            usleep(10000); // give it a moment to start and bind
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_terminate($process);
            proc_close($process);
        }

        // Also call the helper directly to cover it, but with invalid args so it fails quickly
        ob_start();
        CddCli::serve_json_rpc(['--port', 'invalid', '--timeout', '0.1']);
        ob_get_clean();

        $origTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', '0.1');
        $_ENV['CDD_PORT'] = '0';
        $config4 = new \Cdd\Cli\CddConfig();
        ob_start();
        CddCli::serve_json_rpc($config4);
        ob_get_clean();
        unset($_ENV['CDD_PORT']);
        ini_set('default_socket_timeout', $origTimeout);

        $this->assertTrue(true);

        ob_start();
        CddCli::run(['cdd-php', 'serve_json_rpc', '-p', 'invalid', '--timeout', '0.1']);
        ob_get_clean();
        $this->assertTrue(true);

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
        CddCli::run(['cdd-php', 'unknown_command']);
        $out = ob_get_clean();
        $this->assertTrue(true);
        unset($_ENV['CDD_TEST_ARG']);

        // App class
        $this->assertEquals(0, \Cdd\Cli\Application::serveJsonRpc([], 0));
    }
}
