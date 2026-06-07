<?php

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class MockStream2
{
    public $context;
    public static $buffer = "";
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }
    public function stream_read($count)
    {
        if (empty(self::$buffer)) {
            return false;
        }
        $ret = substr(self::$buffer, 0, $count);
        self::$buffer = substr(self::$buffer, $count);
        return $ret;
    }
    public function stream_write($data)
    {
        $req = json_decode(trim($data), true);
        if ($req && isset($req['id'])) {
            $res = ['jsonrpc' => '2.0', 'id' => $req['id'], 'result' => ['content' => ['text' => 'hi']]];
            self::$buffer .= json_encode($res) . "\n";
            // also trigger an error for next call
            $res2 = ['jsonrpc' => '2.0', 'id' => $req['id'], 'error' => ['message' => 'err']];
            self::$buffer .= json_encode($res2) . "\n";
        }
        return strlen($data);
    }
    public function stream_eof()
    {
        return empty(self::$buffer);
    }
    public function stream_stat()
    {
        return [];
    }
    public function stream_flush()
    {
        return true;
    }
}
if (!in_array("mock2", stream_get_wrappers())) {
    stream_wrapper_register("mock2", "Cdd\\Tests\\Cli\\MockStream2");
}

class CddCliSampleLlmTest extends TestCase
{
    public function testLlm()
    {
        $mock = fopen('mock2://test', 'r+');
        CddCli::$testInStream = $mock;
        CddCli::$testOutStream = $mock;

        $res = CddCli::sample_llm([['role' => 'user','content' => 'x']], 10, 'sys');
        $this->assertEquals('hi', $res['content']['text']);

        try {
            CddCli::sample_llm([['role' => 'user','content' => 'x']], 10, 'sys');
        } catch (\Exception $e) {
        }

        CddCli::$testInStream = null;
        CddCli::$testOutStream = null;
    }
}
