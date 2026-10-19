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
 * Verifies `\Cdd\Tests\emit_mock_tests` output and runtime behavior.
 */
class ParseEmitMockTestsTest extends TestCase
{
    /**
     * Tests emitting mock tests with and without schemas and executing generated DAO tests.
     *
     * @return void
     */
    public function testEmitMockTests(): void
    {
        $schemas = [
            'MockUserExec' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                ],
            ],
        ];
        $files = \Cdd\Tests\emit_mock_tests($schemas);

        $this->assertTrue(isset($files['Daos/MockUserExecDaoTest.php']));
        $this->assertTrue(isset($files['Database/DatabaseTest.php']));
        $this->assertTrue(isset($files['Seeder/SeederTest.php']));

        $this->assertStringContainsString('class MockUserExecDaoTest', $files['Daos/MockUserExecDaoTest.php']);
        $this->assertStringContainsString('expectException(MockServerError::class)', $files['Daos/MockUserExecDaoTest.php']);
        $this->assertStringContainsString('class DatabaseTest', $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString("hasTable('mockuserexecs')", $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString('class SeederTest', $files['Seeder/SeederTest.php']);

        $daoModularFiles = \Cdd\Daos\emit_modular($schemas);
        foreach ($daoModularFiles as $filename => $daoCode) {
            if ($filename === 'MockServerError.php' && class_exists(\Api\Exceptions\MockServerError::class)) {
                continue;
            }
            if ($filename === 'DaoFactory.php' && class_exists(\Api\Daos\DaoFactory::class)) {
                continue;
            }
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $daoCode));
        }

        $adaptedDaoTestCode = str_replace(
            ['<?php', 'declare(strict_types=1);', 'use PHPUnit\\Framework\\TestCase;'],
            ['', '', 'use Cdd\\Tests\\Framework\\TestCase;'],
            $files['Daos/MockUserExecDaoTest.php']
        );
        eval($adaptedDaoTestCode);
        $daoTestInstance = new \Api\Tests\Daos\MockUserExecDaoTest();
        [$passed, $failed] = $daoTestInstance->runAll();
        $this->assertEquals(1, $passed);
        $this->assertEquals(0, $failed);

        $emptyFiles = \Cdd\Tests\emit_mock_tests([]);
        $this->assertArrayHasKey('Seeder/SeederTest.php', $emptyFiles);
        $this->assertStringContainsString('$this->assertNotNull($capsule->getConnection());', $emptyFiles['Seeder/SeederTest.php']);
        $this->assertStringContainsString('$this->assertNotNull($capsule->getConnection());', $emptyFiles['Database/DatabaseTest.php']);
    }
}
