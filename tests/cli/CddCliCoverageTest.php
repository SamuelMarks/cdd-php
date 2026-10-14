<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class CddCliCoverageTest extends TestCase
{
    public function testSampleLlm()
    {
        $mockIn = fopen('php://memory', 'r+');
        $mockOut = fopen('php://memory', 'r+');
        CddCli::$testInStream = $mockIn;
        CddCli::$testOutStream = $mockOut;

        $res = CddCli::sample_llm([['role' => 'user', 'content' => 'x']], 10, 'sys');
        $this->assertNull($res);

        CddCli::$testInStream = null;
        CddCli::$testOutStream = null;
    }

    public function testMcpCoverage()
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_mcp_test_' . uniqid();
        mkdir($tmpDir, 0777, true);
        $specPath = "$tmpDir/spec.json";
        file_put_contents($specPath, json_encode([
            'openapi' => '3.2.0',
            'info' => ['title' => 'MCP Test', 'version' => '1.0.0'],
            'paths' => ['/ping' => ['get' => ['operationId' => 'pingOp', 'responses' => ['200' => ['description' => 'ok']]]]],
        ]));

        $mock = fopen('php://memory', 'r+');
        CddCli::$testInStream = $mock;

        $requests = [
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping', 'params' => []],
            ['jsonrpc' => '2.0', 'method' => 'initialized', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'resources/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 31, 'method' => 'resources/list', 'params' => ['cursor' => '0', 'limit' => 1]],
            ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'cdd://ast']],
            ['jsonrpc' => '2.0', 'id' => 41, 'method' => 'resources/read', 'params' => ['uri' => 'cdd://schema']],
            ['jsonrpc' => '2.0', 'id' => 42, 'method' => 'resources/read', 'params' => ['uri' => 'file://' . getcwd() . '/composer.json']],
            ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'resources/read', 'params' => ['uri' => 'invalid']],
            ['jsonrpc' => '2.0', 'id' => 51, 'method' => 'resources/read', 'params' => ['uri' => 'file:///tmp/outside']],
            ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 61, 'method' => 'tools/list', 'params' => ['cursor' => '0', 'limit' => 1]],
            ['jsonrpc' => '2.0', 'id' => 71, 'method' => 'tools/call', 'params' => ['name' => 'from_openapi', 'arguments' => ['input' => $specPath, 'output' => "$tmpDir/out"]]],
            ['jsonrpc' => '2.0', 'id' => 72, 'method' => 'tools/call', 'params' => ['name' => 'to_openapi', 'arguments' => ['input' => "$tmpDir/out/src/routes.php", 'output' => "$tmpDir/parsed.json"]]],
            ['jsonrpc' => '2.0', 'id' => 73, 'method' => 'tools/call', 'params' => ['name' => 'sync', 'arguments' => ['input' => "$tmpDir/out", 'truth' => 'class']]],
            ['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call', 'params' => ['name' => 'invalid_tool']],
            ['jsonrpc' => '2.0', 'id' => 9, 'method' => 'invalid_method'],
            ['jsonrpc' => '2.0', 'id' => 10, 'method' => 'prompts/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 101, 'method' => 'prompts/list', 'params' => ['cursor' => '0']],
            ['jsonrpc' => '2.0', 'id' => 11, 'method' => 'prompts/get', 'params' => ['name' => 'test']],
            ['jsonrpc' => '2.0', 'id' => 12, 'method' => 'logging/setLevel', 'params' => ['level' => 'debug']],
            ['jsonrpc' => '2.0', 'id' => 13, 'method' => 'resources/templates/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 131, 'method' => 'resources/templates/list', 'params' => ['cursor' => '0']],
            ['jsonrpc' => '2.0', 'id' => 14, 'method' => 'completion/complete', 'params' => ['ref' => ['type' => 'ref'], 'argument' => ['name' => 'arg', 'value' => 'val']]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/cancelled', 'params' => ['requestId' => 10, 'reason' => 'test']],
            ['jsonrpc' => '2.0', 'method' => 'notifications/progress', 'params' => ['progressToken' => '10', 'progress' => 10, 'total' => 100]],
            "invalid json",
            ['jsonrpc' => '2.0', 'id' => 22]
        ];

        foreach ($requests as $req) {
            fwrite($mock, (is_string($req) ? $req : json_encode($req)) . "\n");
        }
        rewind($mock);

        ob_start();
        $code = CddCli::run(['cdd-php', 'mcp']);
        $out = (string) ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString('protocolVersion', $out);
        $this->assertStringContainsString('nextCursor', $out);
        $this->assertTrue(file_exists("$tmpDir/parsed.json"));
        CddCli::$testInStream = null;
        CddCli::$testOutStream = null;
        exec("rm -rf " . escapeshellarg($tmpDir));
    }
}
