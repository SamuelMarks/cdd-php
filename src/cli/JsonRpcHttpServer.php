<?php

/**
 * TCP HTTP JSON-RPC server transport for `cdd-php`.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Handles raw TCP HTTP JSON-RPC connections and dispatches CLI commands.
 */
class JsonRpcHttpServer
{
    /**
     * Processes a single HTTP JSON-RPC connection stream.
     *
     * @param resource $conn The accepted socket stream connection.
     * @param int $chunkSize Read buffer chunk size in bytes.
     * @return string The formatted HTTP response written to the connection.
     */
    public static function handleConnection(mixed $conn, int $chunkSize = 8192): string
    {
        $crlf = chr(13) . chr(10);
        $sep = $crlf . $crlf;
        $request = '';
        while (($data = fread($conn, $chunkSize)) !== false && $data !== '') {
            $request .= $data;
            if (strpos($request, $sep) !== false) {
                break;
            }
        }
        $headersEnd = strpos($request, $sep);

        $body = $request;
        if ($headersEnd !== false) {
            $body = substr($request, $headersEnd + 4);
            if (preg_match('/Content-Length:\s*(\d+)/i', $request, $m)) {
                $len = (int) $m[1];
                while (strlen($body) < $len && ($chunk = fread($conn, $chunkSize)) !== false && $chunk !== '') {
                    $body .= $chunk;
                }
            }
        }

        $reqData = json_decode($body, true);
        $res = ['jsonrpc' => '2.0', 'id' => $reqData['id'] ?? null];

        if (!is_array($reqData) || !isset($reqData['method']) || !is_string($reqData['method'])) {
            $res['error'] = ['code' => -32600, 'message' => 'Invalid Request'];
        } else {
            $m = $reqData['method'];
            $p = $reqData['params'] ?? [];
            $cmdArgs = ['cdd-php', $m];
            if (is_array($p)) {
                foreach ($p as $k => $v) {
                    if (is_int($k)) {
                        $cmdArgs[] = (string) $v;
                    } else {
                        $cmdArgs[] = '--' . $k;
                        $cmdArgs[] = (string) $v;
                    }
                }
            }
            ob_start();
            CddCli::run($cmdArgs);
            $res['result'] = (string) ob_get_clean();
        }
        $resBody = (string) json_encode($res);
        $response = "HTTP/1.1 200 OK{$crlf}Content-Type: application/json{$crlf}Content-Length: " . strlen($resBody) . $sep . $resBody;
        fwrite($conn, $response);
        fclose($conn);
        return $response;
    }

    /**
     * Runs the TCP HTTP JSON-RPC server (`serve_json_rpc`).
     *
     * @param array<int, string> $argv CLI arguments.
     * @return int Exit status code.
     */
    public static function serve(array $argv): int
    {
        $argc = count($argv);
        $port = (int) ($_ENV['CDD_PORT'] ?? 8080);
        $listen = $_ENV['CDD_LISTEN'] ?? '127.0.0.1';
        $timeout = (float) ($_ENV['CDD_TIMEOUT'] ?? ini_get('default_socket_timeout'));
        for ($i = 2; $i < $argc; $i++) {
            if (($argv[$i] === '--port' || $argv[$i] === '-p') && isset($argv[$i + 1])) {
                $port = (int) $argv[++$i];
            } elseif (($argv[$i] === '--listen' || $argv[$i] === '-l') && isset($argv[$i + 1])) {
                $listen = $argv[++$i];
            } elseif ($argv[$i] === '--timeout' && isset($argv[$i + 1])) {
                $timeout = (float) $argv[++$i];
            }
        }
        $serverUrl = "tcp://$listen:$port";
        $socket = @stream_socket_server($serverUrl, $errno, $errstr);
        if (!$socket) {
            fwrite(STDERR, "Error starting server: $errstr ($errno)
");
            return 1;
        }
        echo "JSON-RPC server listening on $serverUrl
";
        while (true) {
            $conn = CddCli::$testSocketConn;
            CddCli::$testSocketConn = null;
            $conn = $conn ?: @stream_socket_accept($socket, $timeout);
            if (!$conn) {
                break;
            }
            self::handleConnection($conn);
        }
        fclose($socket);
        return 0;
    }
}
