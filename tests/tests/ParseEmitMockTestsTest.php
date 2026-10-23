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
        $this->assertTrue(isset($files['Cors/CorsTest.php']));
        $this->assertTrue(isset($files['Validation/ValidationTest.php']));
        $this->assertTrue(isset($files['Auth/AuthTest.php']));
        $this->assertTrue(isset($files['Webhooks/WebhookTriggerTest.php']));

        $this->assertStringContainsString('class MockUserExecDaoTest', $files['Daos/MockUserExecDaoTest.php']);
        $this->assertStringContainsString('expectException(MockServerError::class)', $files['Daos/MockUserExecDaoTest.php']);
        $this->assertStringContainsString('class DatabaseTest', $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString("hasTable('mockuserexecs')", $files['Database/DatabaseTest.php']);
        $this->assertStringContainsString('class SeederTest', $files['Seeder/SeederTest.php']);
        $this->assertStringContainsString('class CorsTest', $files['Cors/CorsTest.php']);
        $this->assertStringContainsString('class ValidationTest', $files['Validation/ValidationTest.php']);
        $this->assertStringContainsString('class AuthTest', $files['Auth/AuthTest.php']);
        $this->assertStringContainsString('class WebhookTriggerTest', $files['Webhooks/WebhookTriggerTest.php']);

        require_once dirname(__DIR__, 2) . '/bin/check_docs.php';
        foreach ($files as $relName => $fileSource) {
            $docRes = \audit_php_source_docs($fileSource, $relName);
            $this->assertEquals($docRes['total'], $docRes['documented']);
        }

        if (!class_exists(\Api\ServerRunner::class)) {
            $serverCliCode = \Cdd\ServerCli\emit();
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $serverCliCode));
        }

        if (!class_exists(\Api\Models\MockUserExec::class)) {
            $modelCode = \Cdd\Schemas\emit('MockUserExec', $schemas['MockUserExec'], 'Api\Models');
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $modelCode));
        }

        if (!class_exists(\Api\Database\DatabaseConnection::class)) {
            $dbCode = \Cdd\Database\emit($schemas, 'Api\Database');
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $dbCode));
        }
        \Api\Database\DatabaseConnection::connect(true);
        \Api\Database\DatabaseConnection::migrate();

        $daoModularFiles = \Cdd\Daos\emit_modular($schemas);
        foreach ($daoModularFiles as $filename => $daoCode) {
            $baseClassName = basename($filename, '.php');
            if ($filename === 'MockServerError.php' && class_exists(\Api\Exceptions\MockServerError::class)) {
                continue;
            }
            if (class_exists("Api\\Daos\\{$baseClassName}") || interface_exists("Api\\Daos\\{$baseClassName}")) {
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

        // Execute generated CorsTest, ValidationTest, AuthTest, and WebhookTriggerTest
        foreach ([
            'Cors/CorsTest.php' => \Api\Tests\Cors\CorsTest::class,
            'Validation/ValidationTest.php' => \Api\Tests\Validation\ValidationTest::class,
            'Auth/AuthTest.php' => \Api\Tests\Auth\AuthTest::class,
            'Webhooks/WebhookTriggerTest.php' => \Api\Tests\Webhooks\WebhookTriggerTest::class,
        ] as $relFile => $testClassName) {
            if (!class_exists($testClassName)) {
                $adapted = str_replace(
                    ['<?php', 'declare(strict_types=1);', 'use PHPUnit\\Framework\\TestCase;'],
                    ['', '', 'use Cdd\\Tests\\Framework\\TestCase;'],
                    $files[$relFile]
                );
                eval($adapted);
            }
            /** @var \Cdd\Tests\Framework\TestCase $inst */
            $inst = new $testClassName();
            [$p, $f] = $inst->runAll();
            $this->assertTrue($p >= 1);
            $this->assertEquals(0, $f);
        }

        // Verify Production Mode (Stateful ORM / Concrete DAO integration)
        putenv('DATABASE_URL=sqlite://:memory:');
        $concreteDao = \Api\Daos\DaoFactory::create('MockUserExec', false);
        $createdUser = $concreteDao->create(['name' => 'prod_user']);
        $prodAuthValid = \Api\AuthMiddleware::authenticate(
            ['Authorization' => 'Bearer user-session-' . $createdUser->id],
            [],
            [['BearerAuth' => []]],
            ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer']],
            true,
            false,
            null,
            'MockUserExec'
        );
        $this->assertEquals(200, $prodAuthValid['status']);
        $this->assertEquals('production', $prodAuthValid['mode'] ?? '');

        $prodAuthInvalid = \Api\AuthMiddleware::authenticate(
            ['Authorization' => 'Bearer user-session-999999'],
            [],
            [['BearerAuth' => []]],
            ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer']],
            true,
            false,
            null,
            'MockUserExec'
        );
        $this->assertEquals(401, $prodAuthInvalid['status']);
        putenv('DATABASE_URL=');

        $emptyFiles = \Cdd\Tests\emit_mock_tests([]);
        $this->assertArrayHasKey('Seeder/SeederTest.php', $emptyFiles);
        $this->assertStringContainsString('$this->assertNotNull($capsule->getConnection());', $emptyFiles['Seeder/SeederTest.php']);
        $this->assertStringContainsString('$this->assertNotNull($capsule->getConnection());', $emptyFiles['Database/DatabaseTest.php']);
    }
}
