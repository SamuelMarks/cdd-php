<?php

declare(strict_types=1);

namespace Cdd\Tests\Client;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParse() {
        $code = "<?php
class ApiClient {
    public function getUsers() {
        \$ch = curl_init();
        \$url = \"{\$this->baseUrl}/users\";
        curl_setopt(\$ch, CURLOPT_URL, \$url);
        curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, 'GET');
        return json_decode(curl_exec(\$ch), true);
    }
}";
        $parsed = \Cdd\Client\parse($code);
        $this->assertEquals('getUsers', $parsed['/users']['get']['operationId']);
    }

    public function testEmit() {
        $emitted = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers']);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, "strtoupper('get')") !== false);
    }

    public function testEmitClassPreservesCode() {
        $paths = [
            '/api/users' => [
                'get' => [
                    'operationId' => 'getUsers'
                ]
            ]
        ];
        
        $existing = "<?php\n\nclass ApiClient {\n    private \$baseUrl;\n\n    public function __construct(string \$baseUrl) {\n        \$this->baseUrl = \$baseUrl;\n    }\n\n    // My existing comment\n    public function myCustomMethod() {}\n}\n";
        $emitted = \Cdd\Client\emit_class($paths, $existing);
        
        $this->assertTrue(strpos($emitted, '// My existing comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function myCustomMethod() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
    }
}
