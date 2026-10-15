<?php

/**
 * Emits PHPUnit tests for generated Mock Server DAOs, Database, and Seeder.
 *
 * @package Cdd\Tests
 */

declare(strict_types=1);

namespace Cdd\Tests;

/**
 * Emits tests for Mock Server DAOs, DB, and Seeder.
 *
 * @param array<string, array<string, mixed>> $schemas Map of schema names to OpenAPI Schema Objects.
 * @return array<string, string> Map of relative test filenames to generated PHP code.
 */
function emit_mock_tests(array $schemas): array
{
    $files = [];
    $bs = chr(92);

    foreach ($schemas as $name => $schema) {
        $daoTest = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Api{$bs}Tests{$bs}Daos;\n\nuse PHPUnit{$bs}Framework{$bs}TestCase;\nuse Api{$bs}Daos{$bs}DaoFactory;\nuse Api{$bs}Daos{$bs}Stub{$name}Dao;\n\nclass {$name}DaoTest extends TestCase {\n";
        $daoTest .= "    public function test{$name}StubDaoThrows(): void {\n";
        $daoTest .= "        \$dao = DaoFactory::create('{$name}', false);\n";
        $daoTest .= "        \$this->assertInstanceOf(Stub{$name}Dao::class, \$dao);\n";
        $daoTest .= "        \$this->expectException({$bs}RuntimeException::class);\n";
        $daoTest .= "        \$dao->getAll();\n";
        $daoTest .= "    }\n";
        $daoTest .= "}\n";
        $files["Daos/{$name}DaoTest.php"] = $daoTest;
    }

    $dbTests = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Api{$bs}Tests{$bs}Database;\n\nuse PHPUnit{$bs}Framework{$bs}TestCase;\nuse Illuminate{$bs}Database{$bs}Capsule{$bs}Manager as Capsule;\nuse Api{$bs}Database{$bs}DatabaseConnection;\n\nclass DatabaseTest extends TestCase {\n";
    $dbTests .= "    public function testEphemeralConnection(): void {\n";
    $dbTests .= "        \$capsule = DatabaseConnection::connect(true);\n";
    $dbTests .= "        \$this->assertInstanceOf(Capsule::class, \$capsule);\n";
    $dbTests .= "        DatabaseConnection::migrate();\n";
    if (!empty($schemas)) {
        $firstTable = strtolower((string) array_keys($schemas)[0]) . 's';
        $dbTests .= "        \$this->assertTrue(Capsule::schema()->hasTable('{$firstTable}'));\n";
    } else {
        $dbTests .= "        \$this->assertNotNull(\$capsule->getConnection());\n";
    }
    $dbTests .= "    }\n";
    $dbTests .= "}\n";
    $files['Database/DatabaseTest.php'] = $dbTests;

    $seederTests = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Api{$bs}Tests{$bs}Seeder;\n\nuse PHPUnit{$bs}Framework{$bs}TestCase;\nuse Api{$bs}Database{$bs}DatabaseConnection;\nuse Api{$bs}Seeder{$bs}DatabaseSeeder;\nuse Api{$bs}Daos{$bs}DaoFactory;\n";
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "use Api{$bs}Daos{$bs}Concrete{$firstName}Dao;\n";
    }
    $seederTests .= "
class SeederTest extends TestCase {
";
    $seederTests .= "    public function testSeeder(): void {
";
    $seederTests .= "        \$capsule = DatabaseConnection::connect(true);
";
    $seederTests .= "        DatabaseConnection::migrate();
";
    $seederTests .= "        \$seeder = new DatabaseSeeder();
";
    $seederTests .= "        \$seeder->seed();
";
    // Check if data exists
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "        \$dao = DaoFactory::create('{$firstName}', true);
";
        $seederTests .= "        \$this->assertInstanceOf(Concrete{$firstName}Dao::class, \$dao);
";
        $seederTests .= "        \$records = \$dao->getAll();
";
        $seederTests .= "        \$this->assertNotEmpty(\$records);
";
    } else {
        $seederTests .= "        \$this->assertNotNull(\$capsule->getConnection());
";
    }
    $seederTests .= "    }
";
    $seederTests .= "}
";
    $files['Seeder/SeederTest.php'] = $seederTests;

    return $files;
}
