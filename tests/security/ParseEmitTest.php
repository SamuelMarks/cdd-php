<?php

declare(strict_types=1);

namespace Cdd\Tests\Security;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $sec = \Cdd\Security\parse(['api_key' => [], 'oauth' => ['read', 'write']]);
        $this->assertEquals(2, count($sec));
        $this->assertTrue(isset($sec[0]['api_key']));
        $this->assertEquals('read', $sec[1]['oauth'][0]);
        
        $emitted = \Cdd\Security\emit($sec);
        $this->assertTrue(strpos($emitted, "requireSecurity('api_key', [])") !== false);
        $this->assertTrue(strpos($emitted, "requireSecurity('oauth', ['read', 'write'])") !== false);
    }
}
