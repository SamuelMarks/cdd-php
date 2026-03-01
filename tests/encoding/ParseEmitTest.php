<?php

declare(strict_types=1);

namespace Cdd\Tests\Encoding;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $encodingData = [
            'contentType' => 'application/xml',
            'headers' => [
                'X-Rate-Limit-Limit' => [
                    'description' => 'Limit',
                    'schema' => ['type' => 'integer']
                ]
            ]
        ];
        
        $parsed = \Cdd\Encoding\parse($encodingData);
        $this->assertEquals('application/xml', $parsed['contentType']);
        $this->assertTrue(isset($parsed['headers']['X-Rate-Limit-Limit']));
        
        $emitted = \Cdd\Encoding\emit($parsed);
        $this->assertTrue(strpos($emitted, '/* Encoding object emitted */') !== false);
    }
}
