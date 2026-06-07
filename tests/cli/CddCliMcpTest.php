<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

class CddCliMcpTest extends TestCase
{
    public function testMcp()
    {
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open('php bin/cdd-php mcp', $descriptorspec, $pipes);
        $this->assertTrue(is_resource($process));

        $assertOut = function ($pipes, $expected) {
            $out = fgets($pipes[1]);
            if (strpos($out, $expected) === false) {
                echo "Expected: $expected, got: $out\n";
                $this->assertTrue(false);
            } else {
                $this->assertTrue(true);
            }
        };

        // initialize
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []]) . "\n");
        $assertOut($pipes, 'protocolVersion');

        // ping
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping', 'params' => []]) . "\n");
        $assertOut($pipes, 'result');

        // initialized
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'initialized', 'params' => []]) . "\n");

        // resources/list
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'resources/list', 'params' => []]) . "\n");
        $assertOut($pipes, 'cdd:\\/\\/ast');

        // resources/read valid
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'cdd://ast']]) . "\n");
        $assertOut($pipes, 'contents');

        // resources/read invalid
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 5, 'method' => 'resources/read', 'params' => ['uri' => 'invalid']]) . "\n");
        $assertOut($pipes, 'Invalid URI');

        // tools/list
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/list', 'params' => []]) . "\n");
        $assertOut($pipes, 'from_openapi');

        // tools/call valid
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'tools/call', 'params' => ['name' => 'to_openapi', 'arguments' => ['input' => 'tests/cli/ParseEmitTest.php', 'output' => 'php://memory']]]) . "\n");
        $assertOut($pipes, 'content');

        // tools/call error
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call', 'params' => ['name' => 'invalid_tool']]) . "\n");
        $assertOut($pipes, 'isError');

        // invalid method
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 9, 'method' => 'invalid_method']) . "\n");
        $assertOut($pipes, 'Method not found');

        // prompts/list
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 10, 'method' => 'prompts/list', 'params' => []]) . "\n");
        $assertOut($pipes, 'prompts');

        // prompts/get
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 11, 'method' => 'prompts/get', 'params' => ['name' => 'test']]) . "\n");
        $assertOut($pipes, 'Prompt not found');

        // logging/setLevel
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 12, 'method' => 'logging/setLevel', 'params' => ['level' => 'debug']]) . "\n");
        $assertOut($pipes, 'result');

        // resources/templates/list
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 13, 'method' => 'resources/templates/list', 'params' => []]) . "\n");
        $assertOut($pipes, 'resourceTemplates');

        // completion/complete
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 14, 'method' => 'completion/complete', 'params' => ['ref' => ['type' => 'ref'], 'argument' => ['name' => 'arg', 'value' => 'val']]]) . "\n");
        $assertOut($pipes, 'completion');

        // notifications/cancelled
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/cancelled', 'params' => ['requestId' => 10, 'reason' => 'test']]) . "\n");
        // No output expected for notification

        // notifications/progress
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/progress', 'params' => ['progressToken' => '10', 'progress' => 10, 'total' => 100]]) . "\n");
        // No output expected for notification

        // pagination - tools/list with cursor
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 15, 'method' => 'tools/list', 'params' => ['cursor' => '0']]) . "\n");
        $assertOut($pipes, 'from_openapi');

        // pagination - resources/list with cursor
        fwrite($pipes[0], json_encode(['jsonrpc' => '2.0', 'id' => 16, 'method' => 'resources/list', 'params' => ['cursor' => '0']]) . "\n");
        $assertOut($pipes, 'cdd:\\/\\/ast');

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    public function testSampleLlm()
    {
        // Test sample_llm natively
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        // We run a tiny inline php script to test sample_llm using CddCli
        $phpCode = 'require_once __DIR__."/src/cli/CddCli.php"; require_once __DIR__."/src/cli/Cli.php"; $res = \Cdd\Cli\CddCli::sample_llm([["role"=>"user", "content"=>"hello"]], 10, "sys"); echo json_encode($res);';
        $process = proc_open('php -r \'' . $phpCode . '\'', $descriptorspec, $pipes, dirname(dirname(__DIR__)));

        // Expect CddCli::sample_llm to write a jsonrpc request to stdout
        $reqLine = fgets($pipes[1]);
        $req = json_decode($reqLine, true);
        $this->assertEquals('sampling/createMessage', $req['method']);
        $this->assertEquals('sys', $req['params']['systemPrompt']);

        // Feed the response back
        $id = $req['id'];
        $resObj = ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['content' => ['role' => 'assistant', 'text' => 'hi']]];
        fwrite($pipes[0], json_encode($resObj) . "\n");

        // Read final result
        $finalLine = fgets($pipes[1]);
        $finalRes = json_decode($finalLine, true);
        $this->assertEquals('hi', $finalRes['content']['text']);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    public function testSampleLlmError()
    {
        // Test sample_llm natively returning an error
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $phpCode = 'require_once __DIR__."/src/cli/CddCli.php"; require_once __DIR__."/src/cli/Cli.php"; try { \Cdd\Cli\CddCli::sample_llm([["role"=>"user", "content"=>"hello"]]); } catch (\Exception $e) { echo $e->getMessage(); }';
        $process = proc_open('php -r \'' . $phpCode . '\'', $descriptorspec, $pipes, dirname(dirname(__DIR__)));

        $reqLine = fgets($pipes[1]);
        $req = json_decode($reqLine, true);
        $id = $req['id'];

        $resObj = ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['message' => 'Sampling denied']];
        fwrite($pipes[0], json_encode($resObj) . "\n");

        $finalLine = fgets($pipes[1]);
        $this->assertEquals('Sampling denied', trim($finalLine));

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}
