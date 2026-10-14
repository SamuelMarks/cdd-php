<?php

/**
 * Tests for `CddCli::sample_llm` over a custom in-memory PHP stream wrapper.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Custom stream wrapper simulating bidirectional MCP stdio for LLM sampling.
 */
class MockStream2
{
    /**
     * @var resource|null Stream context resource.
     */
    public mixed $context = null;

    /**
     * @var string Internal stream buffer.
     */
    public static string $buffer = '';

    /**
     * Opens the virtual stream.
     *
     * @param string $path Stream URI.
     * @param string $mode Open mode.
     * @param int $options Stream options bitmask.
     * @param string|null $opened_path Populated opened path reference.
     * @return bool Always true.
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return true;
    }

    /**
     * Reads up to `$count` bytes from the internal buffer.
     *
     * @param int $count Maximum bytes to read.
     * @return string|false Read bytes or false when empty.
     */
    public function stream_read(int $count): string|false
    {
        if (empty(self::$buffer)) {
            return false;
        }
        $ret = substr(self::$buffer, 0, $count);
        self::$buffer = substr(self::$buffer, $count);
        return $ret;
    }

    /**
     * Writes data into the stream and queues a simulated JSON-RPC response.
     *
     * @param string $data Raw JSON-RPC request line.
     * @return int Number of bytes written.
     */
    public function stream_write(string $data): int
    {
        $req = json_decode(trim($data), true);
        if ($req && isset($req['id'])) {
            if (strpos($data, 'trigger_error') !== false) {
                $res2 = ['jsonrpc' => '2.0', 'id' => $req['id'], 'error' => ['message' => 'err']];
                self::$buffer .= json_encode($res2) . "
";
            } else {
                $res = ['jsonrpc' => '2.0', 'id' => $req['id'], 'result' => ['content' => ['text' => 'hi']]];
                self::$buffer .= json_encode($res) . "
";
            }
        }
        return strlen($data);
    }

    /**
     * Checks whether the buffer is at EOF.
     *
     * @return bool True if buffer is empty.
     */
    public function stream_eof(): bool
    {
        return empty(self::$buffer);
    }

    /**
     * Returns stream stat metadata.
     *
     * @return array<string, mixed> Empty stat array.
     */
    public function stream_stat(): array
    {
        return [];
    }

    /**
     * Flushes stream output.
     *
     * @return bool Always true.
     */
    public function stream_flush(): bool
    {
        return true;
    }
}

if (!in_array('mock2', stream_get_wrappers(), true)) {
    stream_wrapper_register('mock2', "Cdd\Tests\Cli\MockStream2");
}

/**
 * Tests `CddCli::sample_llm` success and error handling.
 */
class CddCliSampleLlmTest extends TestCase
{
    /**
     * Tests `sample_llm` normal response and error exception.
     *
     * @return void
     */
    public function testLlm(): void
    {
        MockStream2::$buffer = '';
        $mock = fopen('mock2://test', 'r+');
        CddCli::$testInStream = $mock;
        CddCli::$testOutStream = $mock;

        $res = CddCli::sample_llm([['role' => 'user', 'content' => 'x']], 10, 'sys');
        $this->assertEquals('hi', $res['content']['text'] ?? null);

        $mock2 = fopen('mock2://test', 'r+');
        CddCli::$testInStream = $mock2;
        CddCli::$testOutStream = $mock2;

        $this->assertThrows(function (): void {
            CddCli::sample_llm([['role' => 'user', 'content' => 'trigger_error']], 10, 'sys');
        }, \Exception::class, 'err');

        CddCli::$testInStream = null;
        CddCli::$testOutStream = null;
    }
}
