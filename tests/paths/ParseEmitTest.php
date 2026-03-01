<?php

declare(strict_types=1);

namespace Cdd\Tests\Paths;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $pathItems = [
            '/users' => [
                'get' => ['operationId' => 'listUsers'],
                'post' => ['operationId' => 'createUser'],
            ]
        ];
        
        $paths = \Cdd\Paths\parse($pathItems);
        
        $emitted = \Cdd\Paths\emit($paths);
        $this->assertTrue(strpos($emitted, 'class ApiController {') !== false);
        $this->assertTrue(strpos($emitted, 'public function listUsers() {') !== false);
        $this->assertTrue(strpos($emitted, 'public function createUser() {') !== false);
    }

    public function testEmitWithExistingCode() {
        $paths = ['/api/users' => ['get' => ['operationId' => 'getUsers']]];
        $existing = "<?php\n\nclass ApiController {\n    // Some comment\n    public function custom() {}\n}\n";
        $emitted = \Cdd\Paths\emit($paths, $existing);
        $this->assertTrue(strpos($emitted, '// Some comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function custom() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
    }
}
