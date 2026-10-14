<?php

/**
 * Tests for Server and Server Variable Object parsing, emission, and validation.
 *
 * @package Cdd\Tests\Servers
 */

declare(strict_types=1);

namespace Cdd\Tests\Servers;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Servers\parse`, `\Cdd\Servers\emit`, and server validators.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting OpenAPI Server Objects.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $serversInput = [['url' => 'https://api.example.com'], ['url' => 'https://dev.example.com']];
        $emitted = "<?php

class ApiServers {
" . \Cdd\Servers\emit($serversInput) . "}
";

        $this->assertTrue(strpos($emitted, "public string \$serverUrl0 = 'https://api.example.com';") !== false);
        $this->assertTrue(strpos($emitted, "public string \$serverUrl1 = 'https://dev.example.com';") !== false);

        $servers = \Cdd\Servers\parse($emitted);
        $this->assertEquals(2, count($servers));
        $this->assertEquals('https://api.example.com', $servers[0]['url']);
        $this->assertEquals('https://dev.example.com', $servers[1]['url']);
    }

    /**
     * Tests parsing syntactically invalid PHP code.
     *
     * @return void
     */
    public function testParseInvalidCode(): void
    {
        $this->assertEquals([], \Cdd\Servers\parse('<?php class {'));
    }

    /**
     * Tests validation error when Server `variables` is not a map.
     *
     * @return void
     */
    public function testValidateServerObjectVariableNotArray(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Servers\validateServerObject([
                'url' => 'http://example.com',
                'variables' => 'not an array',
            ]);
        }, \RuntimeException::class, 'Server "variables" must be a map');
    }

    /**
     * Tests validation error when Server is not an object.
     *
     * @return void
     */
    public function testValidateServerObjectNotArray(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Servers\validateServerObject('not an array');
        }, \RuntimeException::class, 'Server must be an object');
    }

    /**
     * Tests validating and emitting a complete Server Object with variables.
     *
     * @return void
     */
    public function testValidateValidServerObject(): void
    {
        $server = [
            'url' => 'http://{username}.example.com:{port}/{basePath}',
            'description' => 'The production API server',
            'variables' => [
                'username' => [
                    'default' => 'demo',
                    'description' => 'this value is assigned by the service provider, in this example `gigantic-server.com`',
                ],
                'port' => [
                    'enum' => [
                        '8443',
                        '443',
                    ],
                    'default' => '8443',
                ],
                'basePath' => [
                    'default' => 'v2',
                ],
            ],
        ];
        $this->assertDoesNotThrow(function () use ($server): void {
            \Cdd\Servers\validateServerObject($server);
        });
        $this->assertStringContainsString('serverUrl0', \Cdd\Servers\emit([$server]));
    }
}
