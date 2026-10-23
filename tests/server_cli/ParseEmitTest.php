<?php

/**
 * Tests for `ServerRunner` CLI class emission (`\Cdd\ServerCli\emit`).
 *
 * @package Cdd\Tests\ServerCli
 */

declare(strict_types=1);

namespace Cdd\Tests\ServerCli;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\ServerCli\emit`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests emitting `ServerRunner` with and without namespace and verifying AST syntax validity.
     *
     * @return void
     */
    public function testEmitServerCli(): void
    {
        $code = \Cdd\ServerCli\emit();

        $this->assertStringContainsString('class ServerRunner', $code);
        $this->assertStringContainsString('class CorsMiddleware', $code);
        $this->assertStringContainsString('class ValidationMiddleware', $code);
        $this->assertStringContainsString('class IdentityProvider', $code);
        $this->assertStringContainsString('class AuthMiddleware', $code);
        $this->assertStringContainsString('class WebhookDispatcher', $code);
        $this->assertStringContainsString('public function parseArgs(array $argv): void', $code);
        $this->assertStringContainsString('public function run(array $argv): void', $code);
        $this->assertStringContainsString("in_array('--ephemeral', \$argv, true)", $code);
        $this->assertStringContainsString("in_array('--seed', \$argv, true)", $code);
        $this->assertStringContainsString("in_array('--strict-validation', \$argv, true)", $code);
        $this->assertStringContainsString("in_array('--enforce-auth', \$argv, true)", $code);
        $this->assertStringContainsString("in_array('--start-auth-server', \$argv, true)", $code);
        $this->assertStringContainsString('DatabaseConnection::connect($this->isEphemeral)', $code);
        $this->assertStringContainsString('DatabaseConnection::migrate()', $code);
        $this->assertStringContainsString('$seeder->seed()', $code);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($code));

        require_once dirname(__DIR__, 2) . '/bin/check_docs.php';
        $docAudit = \audit_php_source_docs($code, 'ServerRunner.php');
        $this->assertEquals($docAudit['total'], $docAudit['documented']);

        $noNsCode = \Cdd\ServerCli\emit('');
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($noNsCode));

        if (!class_exists(\Api\ServerRunner::class)) {
            eval(str_replace(['<?php', 'declare(strict_types=1);'], '', $code));
        }

        // 1. ServerRunner CLI Flags
        $runner = new \Api\ServerRunner();
        $runner->parseArgs(['server', '--ephemeral', '--seed', '--strict-validation', '--enforce-auth', '--start-auth-server']);
        $this->assertTrue($runner->isEphemeral);
        $this->assertTrue($runner->shouldSeed);
        $this->assertTrue($runner->strictValidation);
        $this->assertTrue($runner->enforceAuth);
        $this->assertTrue($runner->startAuthServer);
        $this->assertFalse($runner->isStubMode);

        // 2. CorsMiddleware: preflight OPTIONS and standard cross-origin
        $preflight = \Api\CorsMiddleware::handle('OPTIONS', ['Origin' => 'https://app.example.com']);
        $this->assertEquals(204, $preflight['status']);
        $this->assertTrue($preflight['preflight']);
        $this->assertEquals('https://app.example.com', $preflight['headers']['Access-Control-Allow-Origin']);

        $corsGet = \Api\CorsMiddleware::handle('GET');
        $this->assertEquals(200, $corsGet['status']);
        $this->assertFalse($corsGet['preflight']);
        $this->assertEquals('*', $corsGet['headers']['Access-Control-Allow-Origin']);

        // 3. ValidationMiddleware: strict vs non-strict and exact constraint failure messages
        $schema = [
            'required' => ['email', 'items'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email', 'minLength' => 5, 'maxLength' => 50],
                'website' => ['type' => 'string', 'format' => 'uri'],
                'items' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 2],
                'age' => ['type' => 'integer', 'minimum' => 18, 'maximum' => 120],
                'score' => ['type' => 'number'],
                'active' => ['type' => 'boolean'],
                'role' => ['type' => 'string', 'enum' => ['admin', 'user']],
            ],
        ];
        $bypassRes = \Api\ValidationMiddleware::validateRequest(['email' => 'bad'], $schema, false);
        $this->assertTrue($bypassRes['valid']);

        $invalidRes = \Api\ValidationMiddleware::validateRequest([
            'email' => 'invalid-email',
            'website' => 'not a uri',
            'items' => [1, 2, 3],
            'age' => 12,
            'role' => 'guest',
        ], $schema, true);
        $this->assertFalse($invalidRes['valid']);
        $this->assertEquals(400, $invalidRes['status']);
        $this->assertTrue(in_array("Field 'email' must match format 'email'", $invalidRes['errors'], true));
        $this->assertTrue(in_array("Array 'items' exceeds maximum length", $invalidRes['errors'], true));

        $validRes = \Api\ValidationMiddleware::validateRequest([
            'email' => 'dev@example.com',
            'website' => 'https://example.com',
            'items' => ['a', 'b'],
            'age' => 30,
            'score' => 99.5,
            'active' => true,
            'role' => 'admin',
        ], $schema, true);
        $this->assertTrue($validRes['valid']);
        $this->assertEquals(200, $validRes['status']);

        // 4. WebhookDispatcher: isolated receiver callback and unknown webhook error
        $dispatched = [];
        $dispatcher = new \Api\WebhookDispatcher(
            ['userSignedUp' => ['post' => ['operationId' => 'onUserSignedUp']]],
            static function (string $url, array $payload, array $headers) use (&$dispatched): array {
                $dispatched = ['url' => $url, 'payload' => $payload, 'headers' => $headers];
                return ['status' => 202, 'body' => (string) json_encode(['accepted' => true])];
            }
        );
        $unknownWh = $dispatcher->trigger('unknownHook', 'http://127.0.0.1:9000/cb', []);
        $this->assertEquals(404, $unknownWh['status']);

        $emptyTargetWh = $dispatcher->trigger('userSignedUp', '', []);
        $this->assertEquals(400, $emptyTargetWh['status']);

        $okWh = $dispatcher->trigger('userSignedUp', 'http://127.0.0.1:9000/cb', ['user_id' => 7]);
        $this->assertEquals(200, $okWh['status']);
        $this->assertEquals(202, $okWh['delivery_status']);
        $this->assertEquals(7, $dispatched['payload']['user_id']);
        $this->assertTrue(in_array('X-Webhook-Event: userSignedUp', $dispatched['headers'], true));

        // 5. ServerRunner MCP mode, Dynamic API-to-Tool Proxy, Root Boundary Enforcement, and URI Protocol Handling
        $runner->parseArgs(['server', 'mcp']);
        $this->assertTrue($runner->isMcpMode);

        $proxyRes = $runner->handleMcpRequest(
            ['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'getUser', 'arguments' => ['id' => 5]]],
            ['getUser' => static fn (array $args): array => ['id' => $args['id'], 'name' => 'Alice']]
        );
        $this->assertStringContainsString('Alice', $proxyRes['result']['content'][0]['text']);

        $unknownToolRes = $runner->handleMcpRequest(['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'missing']]);
        $this->assertTrue($unknownToolRes['result']['isError']);

        $statusUriRes = $runner->handleMcpRequest(['id' => 3, 'method' => 'resources/read', 'params' => ['uri' => 'api://status']]);
        $this->assertStringContainsString('ok', $statusUriRes['result']['contents'][0]['text']);

        $validFileUri = 'file://' . __FILE__;
        $fileUriRes = $runner->handleMcpRequest(['id' => 4, 'method' => 'resources/read', 'params' => ['uri' => $validFileUri]], [], dirname(__DIR__, 2));
        $this->assertTrue(isset($fileUriRes['result']['contents'][0]['text']));

        $outsideBoundaryRes = $runner->handleMcpRequest(['id' => 5, 'method' => 'resources/read', 'params' => ['uri' => 'file:///etc/hosts']], [], dirname(__DIR__, 2));
        $this->assertEquals(-32602, $outsideBoundaryRes['error']['code']);

        $invalidUriRes = $runner->handleMcpRequest(['id' => 6, 'method' => 'resources/read', 'params' => ['uri' => 'invalid://uri']]);
        $this->assertEquals(-32602, $invalidUriRes['error']['code']);

        $pingRes = $runner->handleMcpRequest(['id' => 7, 'method' => 'ping']);
        $this->assertEquals('ready', $pingRes['result']['status']);
    }
}
