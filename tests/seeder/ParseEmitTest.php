<?php

declare(strict_types=1);

namespace Cdd\Tests\Seeder;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testEmitSeeder()
    {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'is_active' => ['type' => 'boolean'],
                    'score' => ['type' => 'number'],
                    'phone' => ['type' => 'string'],
                    'other' => ['type' => 'string']
                ]
            ],
            'Post' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer'],
                    'title' => ['type' => 'string']
                ]
            ]
        ];

        $code = \Cdd\Seeder\emit($schemas);

        $this->assertStringContainsString('class DatabaseSeeder', $code);
        $this->assertStringContainsString('FakerFactory::create(', $code);

        // Assert topological order
        $userPos = strpos($code, '// Seed User');
        $postPos = strpos($code, '// Seed Post');
        $this->assertTrue($userPos !== false);
        $this->assertTrue($postPos !== false);
        $this->assertTrue($userPos < $postPos, "User should be seeded before Post");

        // Assert relationships
        $this->assertStringContainsString('$this->entityPool[\'User\'][array_rand($this->entityPool[\'User\'])]', $code);

        // Assert faker methods
        $this->assertStringContainsString('$this->faker->email()', $code);
        $this->assertStringContainsString('$this->faker->name()', $code);
    }
}
