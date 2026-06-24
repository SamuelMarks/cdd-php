<?php

declare(strict_types=1);

namespace Cdd\Tests\Tests;

use Cdd\Tests\Framework\TestCase;

class ParseEmitMockTestsTest extends TestCase
{
    public function testEmitMockTests()
    {
        $schemas = ['User' => []];
        $files = \Cdd\Tests\emit_mock_tests($schemas);

        $this->assertTrue(isset($files['Daos/UserDaoTest.php']));
        $this->assertTrue(isset($files['Database/DatabaseTest.php']));
        $this->assertTrue(isset($files['Seeder/SeederTest.php']));

        $this->assertStringContainsString('class UserDaoTest', $files['Daos/UserDaoTest.php']);
        $this->assertStringContainsString('class DatabaseTest', $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString('class SeederTest', $files['Seeder/SeederTest.php']);
    }
}
