<?php

/**
 * Tests for Mock Server DAO, Database, and Seeder test emission.
 *
 * @package Cdd\Tests\Tests
 */

declare(strict_types=1);

namespace Cdd\Tests\Tests;

use Cdd\Tests\Framework\TestCase;

/**
 * Verifies `\Cdd\Tests\emit_mock_tests` output.
 */
class ParseEmitMockTestsTest extends TestCase
{
    /**
     * Tests emitting mock tests with and without schemas.
     *
     * @return void
     */
    public function testEmitMockTests(): void
    {
        $schemas = ['User' => []];
        $files = \Cdd\Tests\emit_mock_tests($schemas);

        $this->assertTrue(isset($files['Daos/UserDaoTest.php']));
        $this->assertTrue(isset($files['Database/DatabaseTest.php']));
        $this->assertTrue(isset($files['Seeder/SeederTest.php']));

        $this->assertStringContainsString('class UserDaoTest', $files['Daos/UserDaoTest.php']);
        $this->assertStringContainsString('class DatabaseTest', $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString("hasTable('users')", $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString('class SeederTest', $files['Seeder/SeederTest.php']);

        $emptyFiles = \Cdd\Tests\emit_mock_tests([]);
        $this->assertArrayHasKey('Seeder/SeederTest.php', $emptyFiles);
        $this->assertStringContainsString('$this->assertNotNull($capsule->getConnection());', $emptyFiles['Seeder/SeederTest.php']);
        $this->assertStringContainsString('$this->assertNotNull($capsule->getConnection());', $emptyFiles['Database/DatabaseTest.php']);
    }
}
