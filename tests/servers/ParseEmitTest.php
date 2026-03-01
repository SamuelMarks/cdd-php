<?php

declare(strict_types=1);

namespace Cdd\Tests\Servers;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $serversInput = [['url' => 'https://api.example.com'], ['url' => 'https://dev.example.com']];
        $emitted = "<?php\n\nclass ApiServers {\n" . \Cdd\Servers\emit($serversInput) . "}\n";
        
        $this->assertTrue(strpos($emitted, "public string \$serverUrl0 = 'https://api.example.com';") !== false);
        $this->assertTrue(strpos($emitted, "public string \$serverUrl1 = 'https://dev.example.com';") !== false);
        
        $servers = \Cdd\Servers\parse($emitted);
        $this->assertEquals(2, count($servers));
        $this->assertEquals('https://api.example.com', $servers[0]['url']);
        $this->assertEquals('https://dev.example.com', $servers[1]['url']);
    }
}
