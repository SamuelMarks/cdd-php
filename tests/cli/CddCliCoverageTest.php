<?php

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

        // We write valid jsonrpc response, but we don't know the ID since it uses uniqid()
        // Wait, if it doesn't match ID, it continues reading until EOF, then returns null.
        $res = CddCli::sample_llm([['role' => 'user','content' => 'x']], 10, 'sys');
        $this->assertEquals(null, $res);

        CddCli::$testInStream = null;
        CddCli::$testOutStream = null;
    }

    public function testMcpCoverage()
    {
        $mock = fopen('php://memory', 'r+');
        CddCli::$testInStream = $mock;

        $requests = [
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping', 'params' => []],
            ['jsonrpc' => '2.0', 'method' => 'initialized', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'resources/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'resources/list', 'params' => ['cursor' => '0']],
            ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'cdd://ast']],
            ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'cdd://schema']],
            ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'file://' . getcwd() . '/composer.json']],
            ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'resources/read', 'params' => ['uri' => 'invalid']],
            ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'resources/read', 'params' => ['uri' => 'file:///tmp/outside']],
            ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/list', 'params' => ['cursor' => '0']],
            // we will skip to_openapi tools here to avoid warnings, or we just pass valid args
            ['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call', 'params' => ['name' => 'invalid_tool']],
            ['jsonrpc' => '2.0', 'id' => 9, 'method' => 'invalid_method'],
            ['jsonrpc' => '2.0', 'id' => 10, 'method' => 'prompts/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 10, 'method' => 'prompts/list', 'params' => ['cursor' => '0']],
            ['jsonrpc' => '2.0', 'id' => 11, 'method' => 'prompts/get', 'params' => ['name' => 'test']],
            ['jsonrpc' => '2.0', 'id' => 12, 'method' => 'logging/setLevel', 'params' => ['level' => 'debug']],
            ['jsonrpc' => '2.0', 'id' => 13, 'method' => 'resources/templates/list', 'params' => []],
            ['jsonrpc' => '2.0', 'id' => 13, 'method' => 'resources/templates/list', 'params' => ['cursor' => '0']],
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
        CddCli::run(['cdd-php', 'mcp']);
        $out = ob_get_clean();

        $this->assertTrue(strpos($out, 'protocolVersion') !== false);
        CddCli::$testInStream = null;
        CddCli::$testOutStream = null;
    }
}
