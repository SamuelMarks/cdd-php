<?php

/**
 * Tests for PHPUnit route test parsing and emission (`\Cdd\Tests`).
 *
 * @package Cdd\Tests\Tests
 */

declare(strict_types=1);

namespace Cdd\Tests\Tests;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Tests\parse`, `\Cdd\Tests\emit`, and `\Cdd\Tests\emit_modular`.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting route test methods.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $code = "<?php

\$this->get('/api/users');
";
        $parsed = \Cdd\Tests\parse($code);

        $this->assertTrue(isset($parsed['get']['/api/users']));

        $emitted = \Cdd\Tests\emit('get', '/api/users', ['responses' => ['200' => []]]);
        $this->assertTrue(strpos($emitted, "\$this->call('get', '/api/users')") !== false);
        $this->assertTrue(strpos($emitted, '$this->assertEquals(200') !== false);
    }

    /**
     * Tests emitting `additionalOperations` and composable test closures.
     *
     * @return void
     */
    public function testEmitAdditionalOperationsAndComposable(): void
    {
        $emitted = \Cdd\Tests\emit('additionalOperations', '/test', [
            'GET' => ['operationId' => 'testId'],
        ], false);
        $this->assertStringContainsString('public function testtestId', $emitted);

        $emitted = \Cdd\Tests\emit('additionalOperations', '/test', [
            'GET' => ['operationId' => 'testId2'],
        ], true);
        $this->assertStringContainsString('function($client, array $mocks = [])', $emitted);
        $this->assertStringContainsString("\$client->call('GET', '/test')", $emitted);
        $this->assertStringContainsString('return $response->status() === 200', $emitted);
    }

    /**
     * Tests parsing `$this->call(method, path)` invocations in test files.
     *
     * @return void
     */
    public function testParseCallMethods(): void
    {
        $code = "<?php
        \$this->call('post', '/test1');
        \$this->call('FOO', '/test2');
        ";
        $parsed = \Cdd\Tests\parse($code);
        $this->assertTrue(isset($parsed['post']['/test1']));
        $this->assertTrue(isset($parsed['additionalOperations']['FOO']['/test2']));
    }

    /**
     * Tests parsing syntactically invalid PHP test code.
     *
     * @return void
     */
    public function testParseCatch(): void
    {
        $code = '<?php syntax error';
        $parsed = \Cdd\Tests\parse($code);
        $this->assertEquals([], $parsed);
    }

    /**
     * Tests status code fallback when responses only contain `default` or `x-` extensions, or non-200 codes.
     *
     * @return void
     */
    public function testEmitWithDefaultOrXResponse(): void
    {
        $emitted = \Cdd\Tests\emit('get', '/api/users', ['responses' => ['default' => [], 'x-test' => []]]);
        $this->assertTrue(strpos($emitted, '$this->assertEquals(200') !== false);

        $emittedCustomCode = \Cdd\Tests\emit('get', '/api/users', ['responses' => ['418' => []]]);
        $this->assertTrue(strpos($emittedCustomCode, '$this->assertEquals(418') !== false);
    }

    /**
     * Tests emitting route tests with `$ref` request bodies.
     *
     * @return void
     */
    public function testEmitWithRequestBody(): void
    {
        $operation = [
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/User',
                        ],
                    ],
                ],
            ],
            'responses' => ['200' => []],
        ];
        $emitted = \Cdd\Tests\emit('post', '/api/users', $operation, true);
        $this->assertStringContainsString("\$mocks['User'] ?? []", $emitted);

        $emittedFalse = \Cdd\Tests\emit('post', '/api/users', $operation, false);
        $this->assertStringContainsString("\$mocks['User'] ?? []", $emittedFalse);
    }

    /**
     * Tests emitting modular route test classes and verifying their AST syntax validity.
     *
     * @return void
     */
    public function testEmitModular(): void
    {
        $paths = [
            '/test' => [
                'get' => ['operationId' => 'testGetRoute', 'responses' => ['default' => [], '404' => []]],
                'additionalOperations' => ['post' => ['operationId' => 'testPostRoute', 'responses' => ['201' => []]]],
            ],
        ];
        $files = \Cdd\Tests\emit_modular($paths);
        $this->assertTrue(isset($files['TestGetRouteTest.php']));
        $this->assertTrue(isset($files['TestPostRouteTest.php']));

        $this->assertStringContainsString('class TestGetRouteTest extends TestCase', $files['TestGetRouteTest.php']);
        $this->assertStringContainsString('class TestPostRouteTest extends TestCase', $files['TestPostRouteTest.php']);
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($files['TestGetRouteTest.php']));
        $this->assertNotNull(\Cdd\Classes\create_parser()->parse($files['TestPostRouteTest.php']));
    }
}
