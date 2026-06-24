<?php

declare(strict_types=1);

namespace Cdd\Tests;

/**
 * Emits tests for Mock Server DAOs, DB, and Seeder.
 */
function emit_mock_tests(array $schemas): array
{
    $files = [];

    foreach ($schemas as $name => $schema) {
        $daoTest = "<?php\n\nnamespace Api\\Tests\\Daos;\n\nuse PHPUnit\\Framework\\TestCase;\nuse Api\\Daos\\DaoFactory;\nuse Api\\Daos\\Stub{$name}Dao;\n\nclass {$name}DaoTest extends TestCase {\n";
        $daoTest .= "    public function test{$name}StubDaoThrows() {\n";
        $daoTest .= "        \$dao = DaoFactory::create('{$name}', false);\n";
        $daoTest .= "        \$this->assertInstanceOf(Stub{$name}Dao::class, \$dao);\n";
        $daoTest .= "        \$this->expectException(\\RuntimeException::class);\n";
        $daoTest .= "        \$dao->getAll();\n";
        $daoTest .= "    }\n";
        $daoTest .= "}\n";
        $files["Daos/{$name}DaoTest.php"] = $daoTest;
    }

    $dbTests = "<?php\n\nnamespace Api\\Tests\\Database;\n\nuse PHPUnit\\Framework\\TestCase;\nuse Illuminate\\Database\\Capsule\\Manager as Capsule;\nuse Api\\Database\\DatabaseConnection;\n\nclass DatabaseTest extends TestCase {\n";
    $dbTests .= "    public function testEphemeralConnection() {\n";
    $dbTests .= "        \$capsule = DatabaseConnection::connect(true);\n";
    $dbTests .= "        \$this->assertInstanceOf(Capsule::class, \$capsule);\n";
    $dbTests .= "        DatabaseConnection::migrate();\n";
    $dbTests .= "        \$this->assertTrue(true);\n";
    $dbTests .= "    }\n";
    $dbTests .= "}\n";
    $files['Database/DatabaseTest.php'] = $dbTests;

    $seederTests = "<?php\n\nnamespace Api\\Tests\\Seeder;\n\nuse PHPUnit\\Framework\\TestCase;\nuse Api\\Database\\DatabaseConnection;\nuse Api\\Seeder\\DatabaseSeeder;\nuse Api\\Daos\\DaoFactory;\n";
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "use Api\\Daos\\Concrete{$firstName}Dao;\n";
    }
    $seederTests .= "\nclass SeederTest extends TestCase {\n";
    $seederTests .= "    public function testSeeder() {\n";
    $seederTests .= "        DatabaseConnection::connect(true);\n";
    $seederTests .= "        DatabaseConnection::migrate();\n";
    $seederTests .= "        \$seeder = new DatabaseSeeder();\n";
    $seederTests .= "        \$seeder->seed();\n";
    // Check if data exists
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "        \$dao = DaoFactory::create('{$firstName}', true);\n";
        $seederTests .= "        \$this->assertInstanceOf(Concrete{$firstName}Dao::class, \$dao);\n";
        $seederTests .= "        \$records = \$dao->getAll();\n";
        $seederTests .= "        \$this->assertNotEmpty(\$records);\n";
    } else {
        $seederTests .= "        \$this->assertTrue(true);\n";
    }
    $seederTests .= "    }\n";
    $seederTests .= "}\n";
    $files['Seeder/SeederTest.php'] = $seederTests;

    return $files;
}
