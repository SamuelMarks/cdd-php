<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class CddCliExtraCoverageTest extends TestCase
{
    public function testJsonRpcConnectionHandling(): void
    {
        // 1. Valid JSON-RPC request with positional and named params and Content-Length header
        $reqBody = (string) json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => '--version',
            'params' => [0 => '-v', 'help' => '1'],
        ]);
        $httpReq = "POST / HTTP/1.1\r\nHost: localhost\r\nContent-Length: " . strlen($reqBody) . "\r\n\r\n" . $reqBody;

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $httpReq);
        rewind($stream);

        CddCli::handleJsonRpcConnection($stream);

        // 2. Invalid JSON-RPC request (missing method)
        $invalidBody = (string) json_encode(['jsonrpc' => '2.0', 'id' => 2]);
        $invalidHttp = "POST / HTTP/1.1\r\nContent-Length: " . strlen($invalidBody) . "\r\n\r\n" . $invalidBody;
        $stream2 = fopen('php://memory', 'r+');
        fwrite($stream2, $invalidHttp);
        rewind($stream2);
        CddCli::handleJsonRpcConnection($stream2);

        // 3. Verify chunked body reading (chunkSize = 16) and serve_json_rpc accept loop
        $body = (string) json_encode(['jsonrpc' => '2.0', 'id' => 99, 'method' => '--version', 'params' => ['input' => 'x', 0 => 'y']]);
        $chunkedHttp = "POST / HTTP/1.1\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body;
        $stream3 = fopen('php://memory', 'r+');
        fwrite($stream3, $chunkedHttp);
        rewind($stream3);
        CddCli::handleJsonRpcConnection($stream3, 16);

        $stream4 = fopen('php://memory', 'r+');
        fwrite($stream4, $chunkedHttp);
        rewind($stream4);
        CddCli::$testSocketConn = $stream4;
        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'serve_json_rpc', '--port', '0', '--timeout', '0.01']));
        ob_end_clean();

        // 4. Test invalid listen address in serve_json_rpc to cover socket error branch
        ob_start();
        $code = CddCli::run(['cdd-php', 'serve_json_rpc', '--listen', '256.256.256.256', '--port', '99999', '--timeout', '0.1']);
        ob_end_clean();
        $this->assertEquals(1, $code);
    }

    public function testSyncAndToOpenApiAllBranches(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_full_sync_' . uniqid();
        mkdir("$tmpDir/src", 0777, true);

        file_put_contents("$tmpDir/openapi.json", json_encode([
            'openapi' => '3.2.0',
            'info' => ['title' => 'Spec Truth API', 'version' => '2.0.0'],
            'paths' => ['/existing' => ['get' => ['operationId' => 'existingOp', 'responses' => ['200' => ['description' => 'ok']]]]],
        ]));

        file_put_contents("$tmpDir/src/api_metadata.php", "<?php return ['info' => ['title' => 'Meta Title', 'version' => '1.2.3'], 'jsonSchemaDialect' => 'https://json-schema.org/draft/2020-12/schema'];\n");
        file_put_contents("$tmpDir/src/routes.php", "<?php\nRoute::get('/users', 'listUsers');\n");
        file_put_contents("$tmpDir/src/ApiClient.php", "<?php\nclass ApiClient {\n    public function listUsers() {\n        \$url = \"{\$this->baseUrl}/users\";\n        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));\n        curl_exec(\$ch);\n    }\n}\n");
        file_put_contents("$tmpDir/src/ApiController.php", "<?php\nclass ApiController {\n    /**\n     * List all users\n     */\n    public function listUsers() {}\n}\n");
        file_put_contents("$tmpDir/src/api_cli.php", "<?php\nif (\$command === 'listUsers') {}\n");
        file_put_contents("$tmpDir/src/ApiServers.php", "<?php\nclass ApiServers {\n    /** Main Server */\n    public string \$serverUrl0 = 'https://api.example.com';\n}\n");
        file_put_contents("$tmpDir/Webhooks.php", "<?php\ninterface WebhookHandlers {\n    public function onUserCreated(array \$request): array;\n}\n");
        file_put_contents("$tmpDir/src/ApiTests.php", "<?php\nclass ApiTests {\n    public function testListUsers() { \$this->get('/users'); }\n}\n");

        // Sync with positional dir and --truth spec
        ob_start();
        $code = CddCli::run(['cdd-php', 'sync', $tmpDir, '-t', 'spec']);
        $out = (string) ob_get_clean();
        $this->assertEquals(0, $code);
        $this->assertStringContainsString('Synchronized codebase', $out);

        // Also test ComposableTests.php branch in sync
        file_put_contents("$tmpDir/src/ComposableTests.php", "<?php return [];\n");
        ob_start();
        $code2 = CddCli::run(['cdd-php', 'sync', '-i', $tmpDir]);
        ob_end_clean();
        $this->assertEquals(0, $code2);

        // Test to_openapi with positional file (combined routes + controller + cli + servers + metadata)
        $combinedFile = "$tmpDir/combined.php";
        file_put_contents($combinedFile, "<?php\n"
            . "Route::get('/users', 'getUsers');\n"
            . "class ApiController { /** Summary */ public function getusers() {} }\n"
            . "if (\$command === 'getusers') {}\n"
            . "class ApiServers { public string \$serverUrl0 = 'https://api.example.com'; }\n");
        ob_start();
        $code3 = CddCli::run(['cdd-php', 'to_openapi', $combinedFile]);
        $jsonOut = (string) ob_get_clean();
        $this->assertEquals(0, $code3);
        $decoded = json_decode($jsonOut, true);
        $this->assertEquals('Meta Title', $decoded['info']['title']);
        $this->assertArrayHasKey('/users', $decoded['paths']);

        // Test to_openapi on ApiClient (curl_exec branch)
        ob_start();
        $code4 = CddCli::run(['cdd-php', 'to_openapi', '-i', "$tmpDir/src/ApiClient.php"]);
        $clientJson = (string) ob_get_clean();
        $this->assertEquals(0, $code4);
        $this->assertStringContainsString('listUsers', $clientJson);

        exec("rm -rf " . escapeshellarg($tmpDir));
    }

    public function testFromOpenApiInputDirAndFlags(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_input_dir_' . uniqid();
        $emptyDir = sys_get_temp_dir() . '/cdd_empty_dir_' . uniqid();
        $outDir = sys_get_temp_dir() . '/cdd_out_dir_' . uniqid();
        mkdir($tmpDir, 0777, true);
        mkdir($emptyDir, 0777, true);

        // Non-existent sync directory
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'sync', '-i', "$tmpDir/nonexistent_sync_dir"]));
        ob_end_clean();

        // Non-existent spec file
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', '-i', "$tmpDir/nonexistent_spec.json"]));
        ob_end_clean();

        // Non-existent input dir
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', '-d', "$tmpDir/nonexistent"]));
        ob_end_clean();

        // Empty input dir (no .json files)
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', '-d', $emptyDir]));
        ob_end_clean();

        // Valid input dir with positional output dir and flags
        file_put_contents("$tmpDir/api.json", json_encode([
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

        exec("rm -rf " . escapeshellarg($tmpDir) . " " . escapeshellarg($emptyDir) . " " . escapeshellarg($outDir));
    }

    public function testToDocsJsonEdgeCases(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_docs_edge_' . uniqid();
        mkdir($tmpDir, 0777, true);

        // Invalid JSON file
        $badFile = "$tmpDir/bad.json";
        file_put_contents($badFile, "{not valid json");
        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'to_docs_json', '-i', $badFile]));
        ob_end_clean();

        // Spec with path-level parameters and operation with snake_case operationId, parameters, and requestBody
        $goodFile = "$tmpDir/good.json";
        file_put_contents($goodFile, json_encode([
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

        // Output to directory (is_dir($outFileDocs) branch)
        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $goodFile, '-o', $tmpDir]));
        ob_end_clean();
        $this->assertTrue(file_exists("$tmpDir/docs.json"));

        // Output to stdout (no -o flag)
        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $goodFile]));
        $stdoutDocs = (string) ob_get_clean();
        $this->assertStringContainsString('createPet', $stdoutDocs);

        // Also test Application::serveJsonRpc with argc > 1
        ob_start();
        $this->assertEquals(0, \Cdd\Cli\Application::serveJsonRpc(['cdd-php', '--version'], 2));
        ob_end_clean();

        exec("rm -rf " . escapeshellarg($tmpDir));
    }
}
