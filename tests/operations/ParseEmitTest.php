<?php

declare(strict_types=1);

namespace Cdd\Tests\Operations;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase {
    public function testParseAndEmit() {
        $params = [
            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
        ];
        $body = [
            'required' => true,
            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/User']]],
        ];
        $responses = [
            '200' => ['content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/User']]]],
        ];
        
        $operation = \Cdd\Operations\parse('createUser', $params, $responses, $body, 'Create a user');
        
        $this->assertEquals('createUser', $operation['operationId']);
        $this->assertEquals('Create a user', $operation['summary']);
        $this->assertTrue(isset($operation['requestBody']));
        
        $emitted = \Cdd\Operations\emit($operation);
        $this->assertTrue(strpos($emitted, 'public function createUser(int $id, User $body): User {') !== false);
    }
}
