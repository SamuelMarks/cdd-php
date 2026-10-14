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

    foreach ($schemas as $name => $schema) {
        $daoTest = "<?php

declare(strict_types=1);

namespace Api\Tests\Daos;

use PHPUnit\Framework\TestCase;
use Api\Daos\DaoFactory;
use Api\Daos\Stub{$name}Dao;

class {$name}DaoTest extends TestCase {
";
        $daoTest .= "    public function test{$name}StubDaoThrows(): void {
";
        $daoTest .= "        \$dao = DaoFactory::create('{$name}', false);
";
        $daoTest .= "        \$this->assertInstanceOf(Stub{$name}Dao::class, \$dao);
";
        $daoTest .= "        \$this->expectException(\RuntimeException::class);
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

namespace Api\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Api\Database\DatabaseConnection;

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

namespace Api\Tests\Seeder;

use PHPUnit\Framework\TestCase;
use Api\Database\DatabaseConnection;
use Api\Seeder\DatabaseSeeder;
use Api\Daos\DaoFactory;
";
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "use Api\Daos\Concrete{$firstName}Dao;
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
