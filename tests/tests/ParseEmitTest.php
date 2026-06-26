<?php

declare(strict_types=1);

namespace Cdd\Tests\Tests;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testParseAndEmit()
    {
        $code = "<?php

\$this->get('/api/users');
";
        $parsed = \Cdd\Tests\parse($code);

        $this->assertTrue(isset($parsed['get']['/api/users']));

        $emitted = \Cdd\Tests\emit('get', '/api/users', ['responses' => ['200' => []]]);
        $this->assertTrue(strpos($emitted, "\$this->call('get', '/api/users')") !== false);
        $this->assertTrue(strpos($emitted, "\$this->assertEquals(200") !== false);
    }

    public function testEmitAdditionalOperationsAndComposable()
    {
        $emitted = \Cdd\Tests\emit('additionalOperations', '/test', [
            'GET' => ['operationId' => 'testId']
        ], false);
        $this->assertStringContainsString('public function testtestId', $emitted);

        $emitted = \Cdd\Tests\emit('additionalOperations', '/test', [
            'GET' => ['operationId' => 'testId2']
        ], true);
        $this->assertStringContainsString('function($client, array $mocks = [])', $emitted);
        $this->assertStringContainsString("\$client->call('GET', '/test')", $emitted);
        $this->assertStringContainsString("return \$response->status() === 200", $emitted);
    }

    public function testParseCallMethods()
    {
        $code = "<?php
        \$this->call('post', '/test1');
        \$this->call('FOO', '/test2');
        ";
        $parsed = \Cdd\Tests\parse($code);
        $this->assertTrue(isset($parsed['post']['/test1']));
        $this->assertTrue(isset($parsed['additionalOperations']['FOO']['/test2']));
    }

    public function testParseCatch()
    {
        $code = "<?php syntax error";
        $parsed = \Cdd\Tests\parse($code);
        $this->assertEquals([], $parsed);
    }

    public function testEmitWithDefaultOrXResponse()
    {
        $emitted = \Cdd\Tests\emit('get', '/api/users', ['responses' => ['default' => [], 'x-test' => []]]);
        $this->assertTrue(strpos($emitted, "\$this->assertEquals(200") !== false);
    }

    public function testEmitWithRequestBody()
    {
        $operation = [
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/User'
                        ]
                    ]
                ]
            ],
            'responses' => ['200' => []]
        ];
        $emitted = \Cdd\Tests\emit('post', '/api/users', $operation, true);
        $this->assertStringContainsString('$mocks[\'User\'] ?? []', $emitted);

        $emittedFalse = \Cdd\Tests\emit('post', '/api/users', $operation, false);
        $this->assertStringContainsString('$mocks[\'User\'] ?? []', $emittedFalse);
    }

    public function testEmitModular()
    {
        $paths = [
            '/test' => [
                'get' => ['operationId' => 'testGetRoute', 'responses' => ['200' => []]],
                'additionalOperations' => ['post' => ['operationId' => 'testPostRoute', 'responses' => ['201' => []]]]
            ]
        ];
        $files = \Cdd\Tests\emit_modular($paths);
        $this->assertTrue(isset($files['TestGetRouteTest.php']));
        $this->assertTrue(isset($files['TestPostRouteTest.php']));

        $this->assertStringContainsString('class TestGetRouteTest extends TestCase', $files['TestGetRouteTest.php']);
        $this->assertStringContainsString('class TestPostRouteTest extends TestCase', $files['TestPostRouteTest.php']);
    }
}
