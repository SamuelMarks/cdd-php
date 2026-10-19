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
        $daoTest = "<?php

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Daos;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}Daos{$bs}DaoFactory;
use Api{$bs}Daos{$bs}Stub{$name}Dao;
use Api{$bs}Exceptions{$bs}MockServerError;

class {$name}DaoTest extends TestCase {
";
        $daoTest .= "    public function test{$name}StubDaoThrows(): void {
";
        $daoTest .= "        putenv('DATABASE_URL=');
";
        $daoTest .= "        \$dao = DaoFactory::create('{$name}', false);
";
        $daoTest .= "        \$this->assertInstanceOf(Stub{$name}Dao::class, \$dao);
";
        $daoTest .= "        \$this->expectException(MockServerError::class);
";
        $daoTest .= "        \$dao->getAll();
";
        $daoTest .= "    }
";
        $daoTest .= "}
";
        $files["Daos/{$name}DaoTest.php"] = $daoTest;
    }

    $dbTests = "<?php

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Database;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Illuminate{$bs}Database{$bs}Capsule{$bs}Manager as Capsule;
use Api{$bs}Database{$bs}DatabaseConnection;

class DatabaseTest extends TestCase {
";
    $dbTests .= "    public function testEphemeralConnection(): void {
";
    $dbTests .= "        \$capsule = DatabaseConnection::connect(true);
";
    $dbTests .= "        \$this->assertInstanceOf(Capsule::class, \$capsule);
";
    $dbTests .= "        DatabaseConnection::migrate();
";
    if (!empty($schemas)) {
        $firstTable = strtolower((string) array_keys($schemas)[0]) . 's';
        $dbTests .= "        \$this->assertTrue(Capsule::schema()->hasTable('{$firstTable}'));
";
    } else {
        $dbTests .= "        \$this->assertNotNull(\$capsule->getConnection());
";
    }
    $dbTests .= "    }
";
    $dbTests .= "}
";
    $files['Database/DatabaseTest.php'] = $dbTests;

    $seederTests = "<?php

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Seeder;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}Database{$bs}DatabaseConnection;
use Api{$bs}Seeder{$bs}DatabaseSeeder;
use Api{$bs}Daos{$bs}DaoFactory;
";
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "use Api{$bs}Daos{$bs}Concrete{$firstName}Dao;
";
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
