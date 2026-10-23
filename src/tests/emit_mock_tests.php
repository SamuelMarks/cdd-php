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
/**
 * Auto-generated DAO test suite for {$name}.
 *
 * @package Api{$bs}Tests{$bs}Daos
 */

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Daos;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}Daos{$bs}DaoFactory;
use Api{$bs}Daos{$bs}Stub{$name}Dao;
use Api{$bs}Exceptions{$bs}MockServerError;

/**
 * Verifies Stub and Concrete DAO behavior for {$name}.
 */
class {$name}DaoTest extends TestCase {
    /**
     * Tests that Stub{$name}Dao throws MockServerError when unimplemented.
     *
     * @return void
     */
    public function test{$name}StubDaoThrows(): void {
        putenv('DATABASE_URL=');
        \$dao = DaoFactory::create('{$name}', false);
        \$this->assertInstanceOf(Stub{$name}Dao::class, \$dao);
        \$this->expectException(MockServerError::class);
        \$dao->getAll();
    }
}
";
        $files["Daos/{$name}DaoTest.php"] = $daoTest;
    }

    $dbTests = "<?php
/**
 * Auto-generated Database connection and migration test suite.
 *
 * @package Api{$bs}Tests{$bs}Database
 */

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Database;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Illuminate{$bs}Database{$bs}Capsule{$bs}Manager as Capsule;
use Api{$bs}Database{$bs}DatabaseConnection;

/**
 * Verifies ephemeral SQLite connection and schema migrations.
 */
class DatabaseTest extends TestCase {
    /**
     * Tests connecting to an ephemeral database and running schema migrations.
     *
     * @return void
     */
    public function testEphemeralConnection(): void {
        \$capsule = DatabaseConnection::connect(true);
        \$this->assertInstanceOf(Capsule::class, \$capsule);
        DatabaseConnection::migrate();
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
}
";
    $files['Database/DatabaseTest.php'] = $dbTests;

    $seederTests = "<?php
/**
 * Auto-generated DatabaseSeeder test suite.
 *
 * @package Api{$bs}Tests{$bs}Seeder
 */

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
/**
 * Verifies topological database seeding.
 */
class SeederTest extends TestCase {
    /**
     * Tests seeding the ephemeral database and querying Concrete DAOs.
     *
     * @return void
     */
    public function testSeeder(): void {
        \$capsule = DatabaseConnection::connect(true);
        DatabaseConnection::migrate();
        \$seeder = new DatabaseSeeder();
        \$seeder->seed();
";
    if (!empty($schemas)) {
        $firstName = array_keys($schemas)[0];
        $seederTests .= "        \$dao = DaoFactory::create('{$firstName}', true);
        \$this->assertInstanceOf(Concrete{$firstName}Dao::class, \$dao);
        \$records = \$dao->getAll();
        \$this->assertNotEmpty(\$records);
";
    } else {
        $seederTests .= "        \$this->assertNotNull(\$capsule->getConnection());
";
    }
    $seederTests .= "    }
}
";
    $files['Seeder/SeederTest.php'] = $seederTests;

    $corsTests = "<?php
/**
 * Auto-generated CORS middleware test suite.
 *
 * @package Api{$bs}Tests{$bs}Cors
 */

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Cors;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}CorsMiddleware;

/**
 * Verifies preflight OPTIONS and cross-origin request handling.
 */
class CorsTest extends TestCase {
    /**
     * Tests preflight OPTIONS and standard cross-origin GET requests.
     *
     * @return void
     */
    public function testPreflightAndCrossOriginRequests(): void {
        \$preflight = CorsMiddleware::handle('OPTIONS', ['Origin' => 'http://localhost:3000']);
        \$this->assertEquals(204, \$preflight['status']);
        \$this->assertTrue(\$preflight['preflight']);
        \$this->assertEquals('http://localhost:3000', \$preflight['headers']['Access-Control-Allow-Origin']);

        \$standard = CorsMiddleware::handle('GET');
        \$this->assertEquals(200, \$standard['status']);
        \$this->assertFalse(\$standard['preflight']);
        \$this->assertEquals('*', \$standard['headers']['Access-Control-Allow-Origin']);
    }
}
";
    $files['Cors/CorsTest.php'] = $corsTests;

    $validationTests = "<?php
/**
 * Auto-generated strict request validation test suite.
 *
 * @package Api{$bs}Tests{$bs}Validation
 */

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Validation;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}ValidationMiddleware;

/**
 * Verifies strict OpenAPI request validation errors.
 */
class ValidationTest extends TestCase {
    /**
     * Tests that malformed requests return 400 Bad Request with detailed constraint failures.
     *
     * @return void
     */
    public function testStrictValidationErrors(): void {
        \$schema = [
            'required' => ['email', 'items'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'items' => ['type' => 'array', 'maxItems' => 2],
            ],
        ];
        \$res = ValidationMiddleware::validateRequest([
            'email' => 'not-an-email',
            'items' => [1, 2, 3],
        ], \$schema, true);

        \$this->assertFalse(\$res['valid']);
        \$this->assertEquals(400, \$res['status']);
        \$this->assertEquals('Bad Request', \$res['error']);
        \$this->assertTrue(in_array(\"Field 'email' must match format 'email'\", \$res['errors'], true));
        \$this->assertTrue(in_array(\"Array 'items' exceeds maximum length\", \$res['errors'], true));
    }
}
";
    $files['Validation/ValidationTest.php'] = $validationTests;

    $userModelName = !empty($schemas) ? (string) array_keys($schemas)[0] : 'User';
    $authTests = "<?php
/**
 * Auto-generated Hybrid Authentication and Integrated IdP test suite.
 *
 * @package Api{$bs}Tests{$bs}Auth
 */

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Auth;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}AuthMiddleware;
use Api{$bs}IdentityProvider;

/**
 * Verifies Mock Mode, Production Mode, and Integrated IdP authentication flows.
 */
class AuthTest extends TestCase {
    /**
     * Tests 401 Unauthorized, 403 Forbidden, and 200 OK in Mock Mode.
     *
     * @return void
     */
    public function testMockModeAuthAndScopes(): void {
        \$reqs = [['BearerAuth' => ['admin']]];
        \$schemes = ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer']];

        \$missing = AuthMiddleware::authenticate([], [], \$reqs, \$schemes, true, true);
        \$this->assertEquals(401, \$missing['status']);

        \$invalid = AuthMiddleware::authenticate(['Authorization' => 'Bearer wrong'], ['X-Mock-Scopes' => 'admin'], \$reqs, \$schemes, true, true);
        \$this->assertEquals(401, \$invalid['status']);

        \$forbidden = AuthMiddleware::authenticate(['Authorization' => 'Bearer mock-token-123', 'X-Mock-Scopes' => 'read'], [], \$reqs, \$schemes, true, true);
        \$this->assertEquals(403, \$forbidden['status']);

        \$valid = AuthMiddleware::authenticate(['Authorization' => 'Bearer mock-token-123', 'X-Mock-Scopes' => 'admin'], [], \$reqs, \$schemes, true, true);
        \$this->assertEquals(200, \$valid['status']);
        \$this->assertTrue(\$valid['authenticated']);
    }

    /**
     * Tests full Integrated IdP registration, login, and token validation against the shared DAO.
     *
     * @return void
     */
    public function testIdpLifecycleWithDao(): void {
        \$idp = new IdentityProvider('{$userModelName}', true);
        \$reg = \$idp->register(['username' => 'alice', 'password' => 'secret123']);
        \$this->assertEquals(201, \$reg['status']);
        \$login = \$idp->login(['username' => 'alice', 'password' => 'secret123']);
        \$this->assertEquals(200, \$login['status']);
        \$token = (string) \$login['data']['access_token'];

        \$reqs = [['BearerAuth' => []]];
        \$schemes = ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer']];
        \$auth = AuthMiddleware::authenticate(['Authorization' => 'Bearer ' . \$token], [], \$reqs, \$schemes, true, true, \$idp, '{$userModelName}');
        \$this->assertEquals(200, \$auth['status']);
        \$this->assertTrue(\$auth['authenticated']);
    }
}
";
    $files['Auth/AuthTest.php'] = $authTests;

    $webhookTests = "<?php
/**
 * Auto-generated Webhook Dispatcher test suite.
 *
 * @package Api{$bs}Tests{$bs}Webhooks
 */

declare(strict_types=1);

namespace Api{$bs}Tests{$bs}Webhooks;

use PHPUnit{$bs}Framework{$bs}TestCase;
use Api{$bs}WebhookDispatcher;

/**
 * Verifies administrative webhook trigger payload dispatching.
 */
class WebhookTriggerTest extends TestCase {
    /**
     * Tests triggering a webhook and verifying receiver payload delivery.
     *
     * @return void
     */
    public function testWebhookTriggerDispatch(): void {
        \$received = [];
        \$dispatcher = new WebhookDispatcher(
            ['orderCreated' => ['post' => ['operationId' => 'onOrderCreated']]],
            function (string \$url, array \$payload, array \$headers) use (&\$received): array {
                \$received = ['url' => \$url, 'payload' => \$payload, 'headers' => \$headers];
                return ['status' => 200, 'body' => (string) json_encode(['received' => true])];
            }
        );

        \$res = \$dispatcher->trigger('orderCreated', 'http://127.0.0.1:9999/webhook', ['order_id' => 42]);
        \$this->assertEquals(200, \$res['status']);
        \$this->assertEquals(42, \$received['payload']['order_id']);
        \$this->assertEquals('http://127.0.0.1:9999/webhook', \$received['url']);
    }
}
";
    $files['Webhooks/WebhookTriggerTest.php'] = $webhookTests;

    return $files;
}
