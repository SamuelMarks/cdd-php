<?php

/**
 * Tests for PHP Client SDK parsing and emission (`\Cdd\Client`).
 *
 * @package Cdd\Tests\Client
 */

declare(strict_types=1);

namespace Cdd\Tests\Client;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Client\parse`, `\Cdd\Client\emit`, and `\Cdd\Client\emit_class`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing standard and additional HTTP operations from an `ApiClient` class.
     *
     * @return void
     */
    public function testParse(): void
    {
        $code = <<<'PHP'
<?php
class ApiClient {
    public function __construct() {}
    private function secret() {}
    abstract public function noStmts();

    public function getUsers() {
        $ch = curl_init();
        $url = "{$this->baseUrl}/users";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
        return json_decode(curl_exec($ch), true);
    }

    public function customMethod() {
        $ch = curl_init();
        $url = "{$this->baseUrl}/custom";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('custom_m'));
        return json_decode(curl_exec($ch), true);
    }
}
PHP;
        $parsed = \Cdd\Client\parse($code);
        $this->assertEquals('getUsers', $parsed['/users']['get']['operationId']);
        $this->assertEquals('customMethod', $parsed['/custom']['additionalOperations']['CUSTOM_M']['operationId']);
    }

    /**
     * Tests parsing syntactically invalid PHP client code.
     *
     * @return void
     */
    public function testParseSyntaxError(): void
    {
        $parsed = \Cdd\Client\parse('<?php class {');
        $this->assertEquals([], $parsed);
    }

    /**
     * Tests emitting client methods with security, requestBody, and consumes options.
     *
     * @return void
     */
    public function testEmit(): void
    {
        $emitted = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers']);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, "strtoupper('get')") !== false);

        $emittedSec = \Cdd\Client\emit('get', '/users', ['operationId' => 'getUsers', 'security' => [['api_key' => []]]]);
        $this->assertTrue(strpos($emittedSec, '$this->requireSecurity') !== false);

        $emittedReqBody = \Cdd\Client\emit('post', '/users', ['operationId' => 'createUsers', 'requestBody' => ['content' => ['application/json' => []]]]);
        $this->assertTrue(strpos($emittedReqBody, 'Content-Type: application/json') !== false);

        $emittedConsumes = \Cdd\Client\emit('post', '/users', ['operationId' => 'createUsers2', 'consumes' => ['application/x-www-form-urlencoded']]);
        $this->assertTrue(strpos($emittedConsumes, 'Content-Type: application/x-www-form-urlencoded') !== false);

        $emittedOctet = \Cdd\Client\emit('post', '/pet/{petId}/uploadImage', [
            'operationId' => 'uploadFile',
            'requestBody' => ['content' => ['application/octet-stream' => ['schema' => ['type' => 'string', 'format' => 'binary']]]],
        ]);
        $this->assertTrue(strpos($emittedOctet, 'Content-Type: application/octet-stream') !== false);
        $this->assertTrue(strpos($emittedOctet, '$rawOctet = isset($body[\'dummy\'])') !== false);
    }

    /**
     * Tests `emit_class` preserving custom methods and generating MCP client methods.
     *
     * @return void
     */
    public function testEmitClassPreservesCode(): void
    {
        $paths = [
            '/api/users' => [
                'get' => [
                    'operationId' => 'getUsers',
                ],
                'additionalOperations' => [
                    'CUSTOM_M' => [
                        'operationId' => 'customMethod',
                    ],
                ],
            ],
        ];

        $existing = "<?php

class ApiClient {
    private \$baseUrl;

    public function __construct(string \$baseUrl) {
        \$this->baseUrl = \$baseUrl;
    }

    // My existing comment
    public function myCustomMethod() {}
}
";
        $emitted = \Cdd\Client\emit_class($paths, $existing);

        $this->assertTrue(strpos($emitted, '// My existing comment') !== false);
        $this->assertTrue(strpos($emitted, 'public function myCustomMethod() {}') !== false);
        $this->assertTrue(strpos($emitted, 'public function getUsers') !== false);
        $this->assertTrue(strpos($emitted, 'public function customMethod') !== false);
        $this->assertTrue(strpos($emitted, 'public function connect_mcp($transport)') !== false);
        $this->assertTrue(strpos($emitted, "\$rpcCall('initialize'") !== false);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($emitted));

        // Roundtrip emitted client class with path parameters and security requirements
        $fullClientCode = \Cdd\Client\emit_class([
            '/users/{userId}' => [
                'get' => [
                    'operationId' => 'getUserById',
                    'security' => [['BearerAuth' => ['users:read']]],
                ],
            ],
        ]);
        $roundtrippedPaths = \Cdd\Client\parse($fullClientCode);
        $this->assertEquals('getUserById', $roundtrippedPaths['/users/{userId}']['get']['operationId']);
        $this->assertEquals([['BearerAuth' => ['users:read']]], $roundtrippedPaths['/users/{userId}']['get']['security']);
        $this->assertEquals('userId', $roundtrippedPaths['/users/{userId}']['get']['parameters'][0]['name']);
        $this->assertEquals('path', $roundtrippedPaths['/users/{userId}']['get']['parameters'][0]['in']);

        // Also test literal $url = 'http://localhost/ping' assignment
        $literalUrlClient = "<?php\nclass LiteralClient {\n    public function ping() {\n        \$ch = curl_init();\n        \$url = '/ping';\n        curl_exec(\$ch);\n    }\n}\n";
        $parsedLiteral = \Cdd\Client\parse($literalUrlClient);
        $this->assertEquals('ping', $parsedLiteral['/ping']['get']['operationId']);
    }

    /**
     * Tests E2E SDK integration with the Integrated Identity Provider (`$client->auth->register`, `login`, `refresh`, `logout`) and accessing a protected endpoint.
     *
     * @return void
     */
    public function testSdkIntegratedIdpEndToEndLifecycle(): void
    {
        $paths = [
            '/protected/profile' => [
                'get' => [
                    'operationId' => 'getProtectedProfile',
                    'security' => [['BearerAuth' => []]],
                ],
            ],
        ];
        $clientCode = \Cdd\Client\emit_class($paths, '', [
            'BearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
        ]);
        $uniqueClass = 'IdpTestApiClient_' . uniqid();
        $evalClientCode = str_replace(
            ['<?php', 'namespace Api;', 'class ApiClient', 'private ApiClient $client;', '__construct(ApiClient $client'],
            ['', '', "class {$uniqueClass}", "private {$uniqueClass} \$client;", "__construct({$uniqueClass} \$client"],
            $clientCode
        );
        eval($evalClientCode);

        if (!class_exists(\Api\IdentityProvider::class)) {
            $serverCliCode = \Cdd\ServerCli\emit();
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $serverCliCode));
        }
        if (!class_exists(\Api\Models\MockUserExec::class)) {
            $schemas = ['MockUserExec' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => 'string']]]];
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', \Cdd\Schemas\emit('MockUserExec', $schemas['MockUserExec'], 'Api\Models')));
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', \Cdd\Database\emit($schemas, 'Api\Database')));
            foreach (\Cdd\Daos\emit_modular($schemas) as $fn => $dCode) {
                if ($fn === 'MockServerError.php' && class_exists(\Api\Exceptions\MockServerError::class)) {
                    continue;
                }
                if ($fn === 'DaoFactory.php' && class_exists(\Api\Daos\DaoFactory::class)) {
                    continue;
                }
                eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $dCode));
            }
        }
        \Api\Database\DatabaseConnection::connect(true);
        \Api\Database\DatabaseConnection::migrate();

        $idp = new \Api\IdentityProvider('MockUserExec', true);
        /** @var object $client */
        $client = new $uniqueClass('http://127.0.0.1:8080');
        $client->setTransport(static function (string $method, string $url, array $headers, array $body) use ($idp): array {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $headerMap = [];
            foreach ($headers as $h) {
                if (str_contains((string) $h, ':')) {
                    [$hk, $hv] = explode(':', (string) $h, 2);
                    $headerMap[trim($hk)] = trim($hv);
                }
            }
            if ($path === '/auth/register') {
                return $idp->register($body);
            }
            if ($path === '/auth/login') {
                return $idp->login($body);
            }
            if ($path === '/auth/refresh') {
                return $idp->refresh($body);
            }
            if ($path === '/auth/logout') {
                return $idp->logout($headerMap['Authorization'] ?? '');
            }
            $authCheck = \Api\AuthMiddleware::authenticate(
                $headerMap,
                [],
                [['BearerAuth' => []]],
                ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer']],
                true,
                true,
                $idp,
                'MockUserExec'
            );
            if (!$authCheck['authenticated']) {
                return ['status' => $authCheck['status'], 'data' => ['error' => $authCheck['error'] ?? 'Unauthorized']];
            }
            return ['status' => 200, 'data' => ['user' => $authCheck['user']->name ?? 'authenticated']];
        });

        // 1. Unauthenticated request returns 401
        $unauthResp = $client->getProtectedProfile();
        $this->assertEquals(401, $unauthResp['status']);

        // 2. Register user via Auth Server
        $regResp = $client->auth->register('carol', 'hunter2');
        $this->assertEquals(201, $regResp['status']);
        $this->assertNotEmpty($client->getSessionToken());

        // 3. Exchange credentials via login() and verify token is automatically attached
        $loginResp = $client->auth->login('carol', 'hunter2');
        $this->assertEquals(200, $loginResp['status']);
        $profileResp = $client->getProtectedProfile();
        $this->assertEquals(200, $profileResp['status']);
        $this->assertEquals('carol', $profileResp['data']['user']);

        // 4. Refresh token and verify new token works
        $refreshResp = $client->auth->refresh();
        $this->assertEquals(200, $refreshResp['status']);
        $this->assertEquals(200, $client->getProtectedProfile()['status']);

        // 5. Logout revokes session token and subsequent protected request returns 401
        $logoutResp = $client->auth->logout();
        $this->assertEquals(200, $logoutResp['status']);
        $this->assertNull($client->getSessionToken());
        $this->assertEquals(401, $client->getProtectedProfile()['status']);
    }
}
