<?php

declare(strict_types=1);

namespace Cdd\Tests\Responses;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $res = \Cdd\Responses\parse('201', 'User', 'Created user');
        
        $this->assertEquals('Created user', $res['201']['description']);
        $this->assertEquals('#/components/schemas/User', $res['201']['content']['application/json']['schema']['$ref']);
        
        $emitted = \Cdd\Responses\emit($res);
        $this->assertEquals(" * @return User\n", $emitted);
    }
}
