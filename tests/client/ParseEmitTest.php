<?php

/**
 * Tests for PHP Client SDK parsing and emission (`\Cdd\Client`).
 *
 * @package Cdd\Tests\Client
 */

declare(strict_types=1);

namespace Cdd\Tests\Client;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Client\parse`, `\Cdd\Client\emit`, and `\Cdd\Client\emit_class`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing standard and additional HTTP operations from an `ApiClient` class.
     *
     * @return void
     */
    public function testParse(): void
    {
        $code = <<<'PHP'
<?php
class ApiClient {
    public function __construct() {}
    private function secret() {}
    abstract public function noStmts();

    public function getUsers() {
        $ch = curl_init();
        $url = "{$this->baseUrl}/users";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        return json_decode(curl_exec($ch), true);
    }

    public function customMethod() {
        $ch = curl_init();
        $url = "{$this->baseUrl}/custom";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('custom_m'));
        return json_decode(curl_exec($ch), true);
    }
}
PHP;
        $parsed = \Cdd\Client\parse($code);
        $this->assertEquals('getUsers', $parsed['/users']['get']['operationId']);
        $this->assertEquals('customMethod', $parsed['/custom']['additionalOperations']['CUSTOM_M']['operationId']);
    }

    /**
     * Tests parsing syntactically invalid PHP client code.
     *
     * @return void
     */
    public function testParseSyntaxError(): void
    {
        $parsed = \Cdd\Client\parse('<?php class {');
        $this->assertEquals([], $parsed);
    }

    /**
     * Tests emitting client methods with security, requestBody, and consumes options.
     *
     * @return void
     */
    public function testEmit(): void
    {
        $emitted = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers']);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, "strtoupper('get')") !== false);

        $emittedSec = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers', 'security' => [['api_key' => []]]]);
        $this->assertTrue(strpos($emittedSec, '$this->requireSecurity') !== false);

        $emittedReqBody = \Cdd\Client\emit('post', '/users', ['operationId' => 'createUsers', 'requestBody' => ['content' => ['application/json' => []]]]);
        $this->assertTrue(strpos($emittedReqBody, 'Content-Type: application/json') !== false);

        $emittedConsumes = \Cdd\Client\emit('post', '/users', ['operationId' => 'createUsers2', 'consumes' => ['application/x-www-form-urlencoded']]);
        $this->assertTrue(strpos($emittedConsumes, 'Content-Type: application/x-www-form-urlencoded') !== false);
    }

    /**
     * Tests `emit_class` preserving custom methods and generating MCP client methods.
     *
     * @return void
     */
    public function testEmitClassPreservesCode(): void
    {
        $paths = [
            '/api/users' => [
                'get' => [
                    'operationId' => 'getUsers',
                ],
                'additionalOperations' => [
                    'CUSTOM_M' => [
                        'operationId' => 'customMethod',
                    ],
                ],
            ],
        ];

        $existing = "<?php

class ApiClient {
    private \$baseUrl;

    public function __construct(string \$baseUrl) {
        \$this->baseUrl = \$baseUrl;
    }

    // My existing comment
    public function myCustomMethod() {}
}
";
        $emitted = \Cdd\Client\emit_class($paths, $existing);

        $this->assertTrue(strpos($emitted, '// My existing comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function myCustomMethod() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, 'public function customMethod') !== false);
        $this->assertTrue(strpos($emitted, 'public function connect_mcp($transport)') !== false);
        $this->assertTrue(strpos($emitted, "\$rpcCall('initialize'") !== false);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($emitted));
    }
}
