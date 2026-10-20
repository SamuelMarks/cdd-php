<?php

/**
 * Tests for programmatic `CddCli` helper methods and subcommand `--help` outputs.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests `CddCli::generate_from_openapi`, `generate_to_openapi`, `generate_docs_json`, `serve_json_rpc`, and `--help`.
 */
class CddCliHelperTest extends TestCase
{
    /**
     * Tests programmatic helper methods and environment variable argument injection.
     *
     * @return void
     */
    public function testHelpers(): void
    {
        ob_start();
        CddCli::generate_from_openapi([]);
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        $config = new \Cdd\Cli\CddConfig();
        $config->target = 'to_sdk';
        ob_start();
        CddCli::generate_from_openapi($config);
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        ob_start();
        CddCli::generate_to_openapi([]);
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        $config2 = new \Cdd\Cli\CddConfig();
        ob_start();
        CddCli::generate_to_openapi($config2);
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        ob_start();
        $this->assertEquals(1, CddCli::generate_docs_json([]));
        ob_get_clean();

        $config3 = new \Cdd\Cli\CddConfig();
        ob_start();
        $this->assertEquals(1, CddCli::generate_docs_json($config3));
        ob_get_clean();

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
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, '0.0.4') !== false);

        ob_start();
        CddCli::run(['cdd-php', '-h']);
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, 'Usage:') !== false);

        $subcommands = ['from_openapi', 'to_openapi', 'to_docs_json', 'serve_json_rpc', 'sync', 'mcp'];
        foreach ($subcommands as $cmd) {
            ob_start();
            CddCli::run(['cdd-php', $cmd, '--help']);
            $out = (string) ob_get_clean();
            $this->assertTrue(strpos($out, 'Usage:') !== false || strpos($out, 'Run the Model') !== false);
        }

        foreach (['to_sdk', 'to_sdk_cli', 'to_server'] as $target) {
            ob_start();
            CddCli::run(['cdd-php', 'from_openapi', $target, '--help']);
            $out = (string) ob_get_clean();
            $this->assertTrue(strpos($out, "Usage: cdd-php from_openapi {$target}") !== false);
        }

        $this->assertEquals(-1, CddCli::printHelp('unknown_subcommand'));

        $_ENV['CDD_TEST_ARG'] = 'val';
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'unknown_command', '--help']));
        ob_get_clean();
        unset($_ENV['CDD_TEST_ARG']);

        $this->assertEquals(0, \Cdd\Cli\Application::serveJsonRpc([], 0));

        CddCli::setStreams(null, null, null);
        $this->assertNull(CddCli::$testInStream);
        $this->assertNull(CddCli::$testOutStream);
        $this->assertNull(CddCli::$testSocketConn);

        $valExc = \Cdd\Exceptions\CddException::validation('invalid spec', '#/info');
        $this->assertInstanceOf(\RuntimeException::class, $valExc);
        $this->assertEquals('invalid spec', $valExc->getMessage());
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::OpenApiValidationError, $valExc->errorCode);
        $this->assertEquals('#/info', $valExc->contextPath);

        $arazzoValExc = \Cdd\Exceptions\CddException::arazzoValidation('invalid arazzo', '#/workflows/0');
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::ArazzoValidationError, $arazzoValExc->errorCode);
        $this->assertEquals('#/workflows/0', $arazzoValExc->contextPath);

        $arazzoRunExc = \Cdd\Exceptions\CddException::arazzoRuntime('missing step', '#/steps/0');
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::ArazzoRuntimeError, $arazzoRunExc->errorCode);

        $jsonDecExc = \Cdd\Exceptions\CddException::jsonDecode('bad json', 'openapi.json');
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::JsonDecodeError, $jsonDecExc->errorCode);
        $this->assertEquals('openapi.json', $jsonDecExc->sourceFile);

        $jsonEncExc = \Cdd\Exceptions\CddException::jsonEncode('encode failed');
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::JsonEncodeError, $jsonEncExc->errorCode);

        $parseExc = \Cdd\Exceptions\CddException::parseError('parse failed', 'src/Models.php');
        $this->assertEquals('parse failed', $parseExc->getMessage());
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::AstParseError, $parseExc->errorCode);
        $this->assertEquals('src/Models.php', $parseExc->sourceFile);

        $emitExc = \Cdd\Exceptions\CddException::emitError('emit failed');
        $this->assertEquals('emit failed', $emitExc->getMessage());
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::CodeEmissionError, $emitExc->errorCode);

        $mcpExc = \Cdd\Exceptions\CddException::mcpError('mcp failed');
        $this->assertEquals('mcp failed', $mcpExc->getMessage());
        $this->assertEquals(\Cdd\Exceptions\CddErrorCode::McpProtocolError, $mcpExc->errorCode);
    }
}
