<?php

/**
 * Tests for `CddCli` JSON-RPC connection handling, `sync`, `to_openapi`, `from_openapi`, and `to_docs_json`.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests additional CLI execution branches and edge cases.
 */
class CddCliExtraCoverageTest extends TestCase
{
    /**
     * Tests `handleJsonRpcConnection` and `serve_json_rpc` socket accept/error handling.
     *
     * @return void
     */
    public function testJsonRpcConnectionHandling(): void
    {
        $crlf = chr(13) . chr(10);
        $sep = $crlf . $crlf;
        $reqBody = (string) json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => '--version',
            'params' => [0 => '-v', 'help' => '1'],
        ]);
        $httpReq = "POST / HTTP/1.1{$crlf}Host: localhost{$crlf}Content-Length: " . strlen($reqBody) . $sep . $reqBody;

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $httpReq);
        rewind($stream);

        $resp1 = CddCli::handleJsonRpcConnection($stream);
        $this->assertStringContainsString("HTTP/1.1 200 OK{$crlf}", $resp1);
        $this->assertStringContainsString("Content-Type: application/json{$crlf}", $resp1);
        $parts1 = explode($sep, $resp1, 2);
        $json1 = json_decode($parts1[1] ?? '', true);
        $this->assertEquals('2.0', $json1['jsonrpc'] ?? '');
        $this->assertEquals(1, $json1['id'] ?? 0);
        $this->assertStringContainsString('0.0.4', (string) ($json1['result'] ?? ''));

        $invalidBody = (string) json_encode(['jsonrpc' => '2.0', 'id' => 2]);
        $invalidHttp = "POST / HTTP/1.1{$crlf}Content-Length: " . strlen($invalidBody) . $sep . $invalidBody;
        $stream2 = fopen('php://memory', 'r+');
        fwrite($stream2, $invalidHttp);
        rewind($stream2);
        $resp2 = CddCli::handleJsonRpcConnection($stream2);
        $parts2 = explode($sep, $resp2, 2);
        $json2 = json_decode($parts2[1] ?? '', true);
        $this->assertEquals(-32600, $json2['error']['code'] ?? 0);
        $this->assertEquals('Invalid Request', $json2['error']['message'] ?? '');

        $body = (string) json_encode(['jsonrpc' => '2.0', 'id' => 99, 'method' => '--version', 'params' => ['input' => 'x', 0 => 'y']]);
        $chunkedHttp = "POST / HTTP/1.1{$crlf}Content-Length: " . strlen($body) . $sep . $body;
        $stream3 = fopen('php://memory', 'r+');
        fwrite($stream3, $chunkedHttp);
        rewind($stream3);
        $resp3 = CddCli::handleJsonRpcConnection($stream3, 16);
        $parts3 = explode($sep, $resp3, 2);
        $json3 = json_decode($parts3[1] ?? '', true);
        $this->assertEquals(99, $json3['id'] ?? 0);
        $this->assertStringContainsString('0.0.4', (string) ($json3['result'] ?? ''));

        $stream4 = fopen('php://memory', 'r+');
        fwrite($stream4, $chunkedHttp);
        rewind($stream4);
        CddCli::$testSocketConn = $stream4;
        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'serve_json_rpc', '--port', '0', '--timeout', '0.01']));
        ob_end_clean();

        ob_start();
        $code = CddCli::run(['cdd-php', 'serve_json_rpc', '--listen', '256.256.256.256', '--port', '99999', '--timeout', '0.1']);
        ob_end_clean();
        $this->assertEquals(1, $code);
    }

    /**
     * Tests `sync` and `to_openapi` across all file discovery and metadata merge branches.
     *
     * @return void
     */
    public function testSyncAndToOpenApiAllBranches(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_full_sync_' . uniqid();
        mkdir("$tmpDir/src", 0777, true);

        file_put_contents("$tmpDir/openapi.json", (string) json_encode([
            'openapi' => '3.2.0',
            'info' => ['title' => 'Spec Truth API', 'version' => '2.0.0'],
            'paths' => ['/existing' => ['get' => ['operationId' => 'existingOp', 'responses' => ['200' => ['description' => 'ok']]]]],
        ]));

        file_put_contents("$tmpDir/src/api_metadata.php", "<?php return ['info' => ['title' => 'Meta Title', 'version' => '1.2.3'], 'jsonSchemaDialect' => 'https://json-schema.org/draft/2020-12/schema'];
");
        file_put_contents("$tmpDir/src/routes.php", "<?php
Route::get('/users', 'listUsers');
");
        file_put_contents(
            "$tmpDir/src/ApiClient.php",
            <<<'PHP'
<?php
class ApiClient {
    public function listUsers() {
        $url = "{$this->baseUrl}/users";
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        curl_exec($ch);
    }
}
PHP
        );
        file_put_contents("$tmpDir/src/ApiController.php", "<?php
class ApiController {
    /**
     * List all users
     */
    public function listUsers() {}
}
");
        file_put_contents("$tmpDir/src/api_cli.php", "<?php
if (\$command === 'listUsers') {}
");
        file_put_contents("$tmpDir/src/ApiServers.php", "<?php
class ApiServers {
    /** Main Server */
    public string \$serverUrl0 = 'https://api.example.com';
}
");
        file_put_contents("$tmpDir/Webhooks.php", "<?php
interface WebhookHandlers {
    public function onUserCreated(array \$request): array;
}
");
        file_put_contents("$tmpDir/src/ApiTests.php", "<?php
class ApiTests {
    public function testListUsers() { \$this->get('/users'); \$this->get('/extra-tested'); }
}
");

        ob_start();
        $code = CddCli::run(['cdd-php', 'sync', $tmpDir, '-t', 'spec']);
        $out = (string) ob_get_clean();
        $this->assertEquals(0, $code);
        $this->assertStringContainsString('Synchronized codebase', $out);

        file_put_contents("$tmpDir/src/ComposableTests.php", "<?php return [];
");
        ob_start();
        $code2 = CddCli::run(['cdd-php', 'sync', '-i', $tmpDir]);
        ob_end_clean();
        $this->assertEquals(0, $code2);

        $combinedFile = "$tmpDir/combined.php";
        file_put_contents($combinedFile, "<?php
"
            . "Route::get('/users', 'getUsers');
"
            . "class ApiController { /** Summary */ public function getusers() {} }
"
            . "if (\$command === 'getusers') {}
"
            . "class ApiServers { public string \$serverUrl0 = 'https://api.example.com'; }
");
        ob_start();
        $code3 = CddCli::run(['cdd-php', 'to_openapi', $combinedFile]);
        $jsonOut = (string) ob_get_clean();
        $this->assertEquals(0, $code3);
        $decoded = json_decode($jsonOut, true);
        $this->assertEquals('Meta Title', $decoded['info']['title']);
        $this->assertArrayHasKey('/users', $decoded['paths']);

        ob_start();
        $code4 = CddCli::run(['cdd-php', 'to_openapi', '-i', "$tmpDir/src/ApiClient.php"]);
        $clientJson = (string) ob_get_clean();
        $this->assertEquals(0, $code4);
        $this->assertStringContainsString('listUsers', $clientJson);

        $cliOnlyDir = "$tmpDir/cli_only";
        mkdir("$cliOnlyDir/src", 0777, true);
        file_put_contents("$cliOnlyDir/src/api_cli.php", "<?php\nif (\$command === 'cliOnlyOp') {}\n");
        ob_start();
        $codeCliOnly = CddCli::run(['cdd-php', 'to_openapi', '-i', $cliOnlyDir]);
        $cliOnlyOut = (string) ob_get_clean();
        $this->assertEquals(0, $codeCliOnly);
        $this->assertStringContainsString('/cli/cliOnlyOp', $cliOnlyOut);

        $this->removeDir($tmpDir);
    }

    /**
     * Tests `from_openapi` with `-d` input directory and CLI flags.
     *
     * @return void
     */
    public function testFromOpenApiInputDirAndFlags(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_input_dir_' . uniqid();
        $emptyDir = sys_get_temp_dir() . '/cdd_empty_dir_' . uniqid();
        $outDir = sys_get_temp_dir() . '/cdd_out_dir_' . uniqid();
        mkdir($tmpDir, 0777, true);
        mkdir($emptyDir, 0777, true);

        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'sync', '-i', "$tmpDir/nonexistent_sync_dir"]));
        ob_end_clean();

        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', '-i', "$tmpDir/nonexistent_spec.json"]));
        ob_end_clean();

        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', '-d', "$tmpDir/nonexistent"]));
        ob_end_clean();

        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', '-d', $emptyDir]));
        ob_end_clean();

        file_put_contents("$tmpDir/api.json", (string) json_encode([
            'openapi' => '3.2.0',
            'info' => ['title' => 'Dir API', 'version' => '1.0.0'],
            'paths' => ['/items' => ['get' => ['operationId' => 'getItems', 'responses' => ['200' => ['description' => 'ok']]]]],
        ]));

        ob_start();
        $code = CddCli::run([
            'cdd-php',
            'from_openapi',
            'to_sdk_cli',
            '-d',
            $tmpDir,
            $outDir,
            '--no-github-actions',
            '--no-installable-package',
            '--tests',
            '--mcp',
        ]);
        ob_end_clean();
        $this->assertEquals(0, $code);
        $this->assertTrue(file_exists("$outDir/src/api_cli.php"));

        $this->removeDir($tmpDir);
        $this->removeDir($emptyDir);
        $this->removeDir($outDir);
    }

    /**
     * Tests `to_docs_json` edge cases and `Application::serveJsonRpc`.
     *
     * @return void
     */
    public function testToDocsJsonEdgeCases(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_docs_edge_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $badFile = "$tmpDir/bad.json";
        file_put_contents($badFile, '{not valid json');
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'to_docs_json', '-i', $badFile]));
        ob_end_clean();

        $goodFile = "$tmpDir/good.json";
        file_put_contents($goodFile, (string) json_encode([
            'openapi' => '3.2.0',
            'info' => ['title' => 'Docs API', 'version' => '1.0.0'],
            'paths' => [
                '/pets' => [
                    'summary' => 'Pets path summary',
                    'post' => [
                        'operationId' => 'create_pet',
                        'parameters' => [['name' => 'limit', 'in' => 'query']],
                        'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object']]]],
                        'responses' => ['200' => ['description' => 'ok']],
                    ],
                ],
            ],
        ]));

        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $goodFile, '-o', $tmpDir]));
        ob_end_clean();
        $this->assertTrue(file_exists("$tmpDir/docs.json"));

        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $goodFile]));
        $stdoutDocs = (string) ob_get_clean();
        $this->assertStringContainsString('createPet', $stdoutDocs);

        ob_start();
        $this->assertEquals(0, \Cdd\Cli\Application::serveJsonRpc(['cdd-php', '--version'], 2));
        ob_end_clean();

        $this->removeDir($tmpDir);
    }
}
