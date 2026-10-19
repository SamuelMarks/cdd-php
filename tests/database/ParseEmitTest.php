<?php

/**
 * Tests for DatabaseConnection and schema migration emission (`\Cdd\Database\emit`).
 *
 * @package Cdd\Tests\Database
 */

declare(strict_types=1);

namespace Cdd\Tests\Database;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Database\emit` migration and connection generation.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests emitting `DatabaseConnection` for multiple property types and verifying AST syntax validity.
     *
     * @return void
     */
    public function testEmitDatabaseConnection(): void
    {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'count' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'active' => ['type' => 'boolean'],
                    'score' => ['type' => 'number'],
                    'tags' => ['type' => 'array'],
                    'meta' => ['type' => 'object'],
                    'created_at_iso' => ['type' => 'string', 'format' => 'date-time'],
                    'bio' => ['type' => 'string', 'format' => 'text'],
                ],
            ],
        ];

        $code = \Cdd\Database\emit($schemas);

        $this->assertStringContainsString('class DatabaseConnection', $code);
        $this->assertStringContainsString('public static function connect(bool $ephemeral = false): Capsule', $code);
        $this->assertStringContainsString("'database' => ':memory:'", $code);
        $this->assertStringContainsString('public static function migrate(): void', $code);

        $this->assertStringContainsString("Capsule::schema()->create('users'", $code);
        $this->assertStringContainsString('$table->id()', $code);
        $this->assertStringContainsString("\$table->integer('count')->nullable()", $code);
        $this->assertStringContainsString("\$table->string('name')->nullable()", $code);
        $this->assertStringContainsString("\$table->boolean('active')->nullable()", $code);
        $this->assertStringContainsString("\$table->float('score')->nullable()", $code);
        $this->assertStringContainsString("\$table->json('tags')->nullable()", $code);
        $this->assertStringContainsString("\$table->json('meta')->nullable()", $code);
        $this->assertStringContainsString("\$table->dateTime('created_at_iso')->nullable()", $code);
        $this->assertStringContainsString("\$table->text('bio')->nullable()", $code);
        $this->assertStringContainsString('$table->timestamps()', $code);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($code));
    }
}
