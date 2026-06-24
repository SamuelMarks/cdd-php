<?php

declare(strict_types=1);

namespace Cdd\Tests\Database;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testEmitDatabaseConnection()
    {
        $schemas = [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'active' => ['type' => 'boolean']
                ]
            ]
        ];

        $code = \Cdd\Database\emit($schemas);

        $this->assertStringContainsString('class DatabaseConnection', $code);
        $this->assertStringContainsString('public static function connect(bool $ephemeral = false): Capsule', $code);
        $this->assertStringContainsString('\'database\' => \':memory:\'', $code);
        $this->assertStringContainsString('public static function migrate(): void', $code);

        // Assert migrations
        $this->assertStringContainsString('Capsule::schema()->create(\'users\'', $code);
        $this->assertStringContainsString('$table->id()', $code);
        $this->assertStringContainsString('$table->string(\'name\')->nullable()', $code);
        $this->assertStringContainsString('$table->boolean(\'active\')->nullable()', $code);
        $this->assertStringContainsString('$table->timestamps()', $code);
    }
}
