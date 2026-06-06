<?php

declare(strict_types=1);

namespace Cdd\Tests\Client;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParse()
    {
        $code = "<?php
class ApiClient {
    public function __construct() {}
    private function secret() {}
    abstract public function noStmts();

    public function getUsers() {
        \$ch = curl_init();
        \$url = \"{\$this->baseUrl}/users\";
        curl_setopt(\$ch, CURLOPT_URL, \$url);
        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        return json_decode(curl_exec(\$ch), true);
    }

    public function customMethod() {
        \$ch = curl_init();
        \$url = \"{\$this->baseUrl}/custom\";
        curl_setopt(\$ch, CURLOPT_URL, \$url);
        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, strtoupper('custom_m'));
        return json_decode(curl_exec(\$ch), true);
    }
}";
        $parsed = \Cdd\Client\parse($code);
        $this->assertEquals('getUsers', $parsed['/users']['get']['operationId']);
        $this->assertEquals('customMethod', $parsed['/custom']['additionalOperations']['CUSTOM_M']['operationId']);
    }

    public function testParseSyntaxError()
    {
        $parsed = \Cdd\Client\parse("<?php class {");
        $this->assertEquals([], $parsed);
    }

    public function testEmit()
    {
        $emitted = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers']);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, "strtoupper('get')") !== false);

        // test security
        $emittedSec = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers', 'security' => [['api_key' => []]]]);
        $this->assertTrue(strpos($emittedSec, '$this->requireSecurity') !== false);

        // test requestBody
        $emittedReqBody = \Cdd\Client\emit('post', '/users', ['operationId' => 'createUsers', 'requestBody' => ['content' => ['application/json' => []]]]);
        $this->assertTrue(strpos($emittedReqBody, "Content-Type: application/json") !== false);

        // test consumes
        $emittedConsumes = \Cdd\Client\emit('post', '/users', ['operationId' => 'createUsers2', 'consumes' => ['application/x-www-form-urlencoded']]);
        $this->assertTrue(strpos($emittedConsumes, "Content-Type: application/x-www-form-urlencoded") !== false);
    }

    public function testEmitClassPreservesCode()
    {
        $paths = [
            '/api/users' => [
                'get' => [
                    'operationId' => 'getUsers'
                ],
                'additionalOperations' => [
                    'CUSTOM_M' => [
                        'operationId' => 'customMethod'
                    ]
                ]
            ]
        ];

        $existing = "<?php\n\nclass ApiClient {\n    private \$baseUrl;\n\n    public function __construct(string \$baseUrl) {\n        \$this->baseUrl = \$baseUrl;\n    }\n\n    // My existing comment\n    public function myCustomMethod() {}\n}\n";
        $emitted = \Cdd\Client\emit_class($paths, $existing);

        $this->assertTrue(strpos($emitted, '// My existing comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function myCustomMethod() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, 'public function customMethod') !== false);
        $this->assertTrue(strpos($emitted, 'public function connect_mcp($transport)') !== false);
        $this->assertTrue(strpos($emitted, '$rpcCall(\'initialize\'') !== false);
    }
}
