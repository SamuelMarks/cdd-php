<?php

/**
 * Subprocess integration tests for `cdd-php mcp` and `CddCli::sample_llm`.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests MCP stdio subprocess protocol messages and LLM sampling over pipes.
 */
class CddCliMcpTest extends TestCase
{
    /**
     * Tests MCP JSON-RPC requests and notifications over stdio subprocess.
     *
     * @return void
     */
    public function testMcp(): void
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
        $process = proc_open("$phpBin bin/cdd-php mcp", $descriptorspec, $pipes);
        $this->assertTrue(is_resource($process));

        $assertOut = function (array $pipes, string $expected): void {
            $out = (string) fgets($pipes[1]);
            $this->assertStringContainsString($expected, $out);
        };

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []]) . "
");
        $assertOut($pipes, 'protocolVersion');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping', 'params' => []]) . "
");
        $assertOut($pipes, 'result');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'initialized', 'params' => []]) . "
");

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'resources/list', 'params' => []]) . "
");
        $assertOut($pipes, 'cdd:\/\/ast');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'cdd://ast']]) . "
");
        $assertOut($pipes, 'contents');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 5, 'method' => 'resources/read', 'params' => ['uri' => 'invalid']]) . "
");
        $assertOut($pipes, 'Invalid URI');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/list', 'params' => []]) . "
");
        $assertOut($pipes, 'from_openapi');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'tools/call', 'params' => ['name' => 'to_openapi', 'arguments' => ['input' => 'tests/cli/ParseEmitTest.php', 'output' => 'php://memory']]]) . "
");
        $assertOut($pipes, 'content');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call', 'params' => ['name' => 'invalid_tool']]) . "
");
        $assertOut($pipes, 'isError');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 9, 'method' => 'invalid_method']) . "
");
        $assertOut($pipes, 'Method not found');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 10, 'method' => 'prompts/list', 'params' => []]) . "
");
        $assertOut($pipes, 'prompts');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 11, 'method' => 'prompts/get', 'params' => ['name' => 'test']]) . "
");
        $assertOut($pipes, 'Prompt not found');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 12, 'method' => 'logging/setLevel', 'params' => ['level' => 'debug']]) . "
");
        $assertOut($pipes, 'result');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 13, 'method' => 'resources/templates/list', 'params' => []]) . "
");
        $assertOut($pipes, 'resourceTemplates');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 14, 'method' => 'completion/complete', 'params' => ['ref' => ['type' => 'ref'], 'argument' => ['name' => 'arg', 'value' => 'val']]]) . "
");
        $assertOut($pipes, 'completion');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/cancelled', 'params' => ['requestId' => 10, 'reason' => 'test']]) . "
");
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/progress', 'params' => ['progressToken' => '10', 'progress' => 10, 'total' => 100]]) . "
");

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 15, 'method' => 'tools/list', 'params' => ['cursor' => '0']]) . "
");
        $assertOut($pipes, 'from_openapi');

        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 16, 'method' => 'resources/list', 'params' => ['cursor' => '0']]) . "
");
        $assertOut($pipes, 'cdd:\/\/ast');

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    /**
     * Tests `CddCli::sample_llm` over a real PHP subprocess pipe.
     *
     * @return void
     */
    public function testSampleLlm(): void
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
        $phpCode = 'require_once __DIR__."/src/cli/McpServer.php"; require_once __DIR__."/src/cli/CddCli.php"; require_once __DIR__."/src/cli/Cli.php"; $res = \Cdd\Cli\CddCli::sample_llm([["role"=>"user", "content"=>"hello"]], 10, "sys"); echo json_encode($res);';
        $process = proc_open($phpBin . ' -r ' . escapeshellarg($phpCode), $descriptorspec, $pipes, dirname(__DIR__, 2));
        $this->assertTrue(is_resource($process));

        $reqLine = (string) fgets($pipes[1]);
        $req = json_decode($reqLine, true);
        $this->assertEquals('sampling/createMessage', $req['method']);
        $this->assertEquals('sys', $req['params']['systemPrompt']);

        $id = $req['id'];
        $resObj = ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['content' => ['role' => 'assistant', 'text' => 'hi']]];
        fwrite($pipes[0], json_encode($resObj) . "
");

        $finalLine = (string) fgets($pipes[1]);
        $finalRes = json_decode($finalLine, true);
        $this->assertEquals('hi', $finalRes['content']['text']);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    /**
     * Tests `CddCli::sample_llm` error propagation over a real PHP subprocess pipe.
     *
     * @return void
     */
    public function testSampleLlmError(): void
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
        $phpCode = 'require_once __DIR__."/src/exceptions/CddException.php"; require_once __DIR__."/src/cli/McpServer.php"; require_once __DIR__."/src/cli/CddCli.php"; require_once __DIR__."/src/cli/Cli.php"; try { \Cdd\Cli\CddCli::sample_llm([["role"=>"user", "content"=>"hello"]]); } catch (\Cdd\Exceptions\CddException $e) { echo $e->getMessage(); }';
        $process = proc_open($phpBin . ' -r ' . escapeshellarg($phpCode), $descriptorspec, $pipes, dirname(__DIR__, 2));
        $this->assertTrue(is_resource($process));

        $reqLine = (string) fgets($pipes[1]);
        $req = json_decode($reqLine, true);
        $id = $req['id'];

        $resObj = ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['message' => 'Sampling denied']];
        fwrite($pipes[0], json_encode($resObj) . "
");

        $finalLine = (string) fgets($pipes[1]);
        $this->assertEquals('Sampling denied', trim($finalLine));

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}
