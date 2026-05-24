<?php

declare(strict_types=1);

namespace Cdd\Tests\Servers;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $serversInput = [['url' => 'https://api.example.com'], ['url' => 'https://dev.example.com']];
        $emitted = "<?php\n\nclass ApiServers {\n" . \Cdd\Servers\emit($serversInput) . "}\n";

        $this->assertTrue(strpos($emitted, "public string \$serverUrl0 = 'https://api.example.com';") !== false);
        $this->assertTrue(strpos($emitted, "public string \$serverUrl1 = 'https://dev.example.com';") !== false);

        $servers = \Cdd\Servers\parse($emitted);
        $this->assertEquals(2, count($servers));
        $this->assertEquals('https://api.example.com', $servers[0]['url']);
        $this->assertEquals('https://dev.example.com', $servers[1]['url']);
    }

    public function testParseInvalidCode()
    {
        $this->assertEquals([], \Cdd\Servers\parse('<?php class {'));
    }

    public function testValidateServerObjectVariableNotArray()
    {
        try {
            \Cdd\Servers\validateServerObject([
                'url' => 'http://example.com',
                'variables' => 'not an array'
            ]);
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Server "variables" must be a map', $e->getMessage());
        }
    }

    public function testValidateServerObjectNotArray()
    {
        try {
            \Cdd\Servers\validateServerObject('not an array');
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Server must be an object', $e->getMessage());
        }
    }

    public function testValidateValidServerObject()
    {
        \Cdd\Servers\validateServerObject([
            'url' => 'http://{username}.example.com:{port}/{basePath}',
            'description' => 'The production API server',
            'variables' => [
                'username' => [
                    'default' => 'demo',
                    'description' => 'this value is assigned by the service provider, in this example `gigantic-server.com`'
                ],
                'port' => [
                    'enum' => [
                        '8443',
                        '443'
                    ],
                    'default' => '8443'
                ],
                'basePath' => [
                    'default' => 'v2'
                ]
            ]
        ]);
        $this->assertTrue(true);
    }
}
