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
        $this->assertTrue(strpos($emitted, '$this->assertIsArray($response->body())') !== false);
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
     * Tests emitting modular route test classes and executing them against working and failing controllers.
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

        $tmpDir = sys_get_temp_dir() . '/cdd_route_test_exec_' . uniqid();
        mkdir("$tmpDir/src/Controllers", 0777, true);
        mkdir("$tmpDir/tests/Routes", 0777, true);
        try {
            $postRouteFiles = \Cdd\Tests\emit_modular([
                '/items' => [
                    'post' => ['operationId' => 'createItemExec', 'responses' => ['201' => []]],
                ],
            ]);
            $testFile = "$tmpDir/tests/Routes/CreateItemExecTest.php";
            $adaptedTestCode = str_replace(
                'use PHPUnit\Framework\TestCase;',
                'use Cdd\Tests\Framework\TestCase;',
                $postRouteFiles['CreateItemExecTest.php']
            );
            file_put_contents($testFile, $adaptedTestCode);

            file_put_contents(
                "$tmpDir/src/Controllers/CreateItemExecController.php",
                "<?php
declare(strict_types=1);
namespace Api\Controllers;
class CreateItemExecController {
    public function __invoke(array \$body = []): array {
        return ['created' => true, 'input' => \$body];
    }
}
"
            );

            require_once $testFile;
            /** @var object $testInstance */
            $testInstance = new \Api\Tests\Routes\CreateItemExecTest('testcreateItemExec');
            $testInstance->testcreateItemExec();

            $brokenRouteFiles = \Cdd\Tests\emit_modular([
                '/broken' => [
                    'get' => ['operationId' => 'brokenRouteExec', 'responses' => ['200' => []]],
                ],
            ]);
            $brokenTestFile = "$tmpDir/tests/Routes/BrokenRouteExecTest.php";
            file_put_contents(
                $brokenTestFile,
                str_replace('use PHPUnit\Framework\TestCase;', 'use Cdd\Tests\Framework\TestCase;', $brokenRouteFiles['BrokenRouteExecTest.php'])
            );
            file_put_contents(
                "$tmpDir/src/Controllers/BrokenRouteExecController.php",
                "<?php
declare(strict_types=1);
namespace Api\Controllers;
class BrokenRouteExecController {
    public function __invoke(): void {
        throw new \RuntimeException('Controller failed');
    }
}
"
            );
            require_once $brokenTestFile;
            /** @var object $brokenTestInstance */
            $brokenTestInstance = new \Api\Tests\Routes\BrokenRouteExecTest('testbrokenRouteExec');
            $this->assertThrows(static function () use ($brokenTestInstance): void {
                $brokenTestInstance->testbrokenRouteExec();
            }, \Exception::class);
        } finally {
            $this->removeDir($tmpDir);
        }
    }
}
