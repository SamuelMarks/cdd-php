<?php

/**
 * Tests for FakerPHP database seeder emission (`\Cdd\Seeder\emit`).
 *
 * @package Cdd\Tests\Seeder
 */

declare(strict_types=1);

namespace Cdd\Tests\Seeder;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Seeder\emit` topological sorting and Faker property generation.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests emitting `DatabaseSeeder` with foreign-key dependencies and verifying AST syntax validity.
     *
     * @return void
     */
    public function testEmitSeeder(): void
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
                    'other' => ['type' => 'string'],
                ],
            ],
            'Post' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer'],
                    'missing_id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                ],
            ],
        ];

        $code = \Cdd\Seeder\emit($schemas);

        $this->assertStringContainsString('class DatabaseSeeder', $code);
        $this->assertStringContainsString('FakerFactory::create(', $code);

        $userPos = strpos($code, '// Seed User');
        $postPos = strpos($code, '// Seed Post');
        $this->assertTrue($userPos !== false);
        $this->assertTrue($postPos !== false);
        $this->assertTrue($userPos < $postPos, 'User should be seeded before Post');

        $this->assertStringContainsString("\$this->entityPool['User'][array_rand(\$this->entityPool['User'])]", $code);
        $this->assertStringContainsString('$this->faker->email()', $code);
        $this->assertStringContainsString('$this->faker->name()', $code);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($code));
    }
}
