<?php

declare(strict_types=1);

namespace Cdd\Tests\Tests;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $code = "<?php

\$this->get('/api/users');
";
        $parsed = \Cdd\Tests\parse($code);
        
        $this->assertTrue(isset($parsed['get']['/api/users']));
        
        $emitted = \Cdd\Tests\emit('get', '/api/users', ['responses' => ['200' => []]]);
        $this->assertTrue(strpos($emitted, "\$this->call('get', '/api/users')") !== false);
        $this->assertTrue(strpos($emitted, "\$this->assertEquals(200") !== false);
    }
}
