<?php

/**
 * Tests for OpenAPI Operation and Callback Object parsing, emission, and validation (`\Cdd\Operations`).
 *
 * @package Cdd\Tests\Operations
 */

declare(strict_types=1);

namespace Cdd\Tests\Operations;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests `\Cdd\Operations\parse`, `\Cdd\Operations\emit`, and operation/callback validators.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests parsing and emitting basic Operation Objects.
     *
     * @return void
     */
    public function testParseAndEmit(): void
    {
        $op = \Cdd\Operations\parse('getUser', [], ['201' => ['description' => 'Created']], null, 'Get a user');
        $this->assertEquals('getUser', $op['operationId']);
        $this->assertEquals('Get a user', $op['summary']);
        $this->assertEquals('Created', $op['responses']['201']['description']);

        $opDefault = \Cdd\Operations\parse('getUser2');
        $this->assertEquals('Success', $opDefault['responses']['200']['description']);

        $emitted = \Cdd\Operations\emit($op);
        $this->assertTrue(strpos($emitted, 'public function getUser()') !== false);
        $this->assertTrue(strpos($emitted, '* Get a user') !== false);

        $parsedFromMethodCode = \Cdd\Operations\parse($emitted);
        $this->assertEquals('getUser', $parsedFromMethodCode['operationId']);
        $this->assertEquals('Get a user', $parsedFromMethodCode['summary']);

        $parsedFromFullPhp = \Cdd\Operations\parse("<?php\nclass C {\n    /**\n     * Do work\n     */\n    public function work(int \$id): string { return ''; }\n}\n");
        $this->assertEquals('work', $parsedFromFullPhp['operationId']);
        $this->assertEquals('Do work', $parsedFromFullPhp['summary']);
        $this->assertEquals('integer', $parsedFromFullPhp['parameters'][0]['schema']['type']);
    }

    /**
     * Tests emitting a full Operation Object with summary, description, tags, externalDocs, callbacks, links, and return types.
     *
     * @return void
     */
    public function testEmitFullCoverage(): void
    {
        $op = [
            'operationId' => 'fullOp',
            'summary' => 'Summary',
            'description' => "Desc line 1
Desc line 2",
            'tags' => ['Tag1', 'Tag2'],
            'externalDocs' => ['url' => 'http://example.com', 'description' => 'Example Docs'],
            'callbacks' => ['myCb' => []],
            'responses' => [
                '200' => [
                    'description' => 'Success',
                    'links' => [
                        'link1' => ['operationId' => 'otherOp'],
                    ],
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
            'parameters' => [
                ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
            ],
            'requestBody' => [
                'content' => ['application/json' => ['schema' => ['type' => 'object']]],
            ],
        ];

        $emitted = \Cdd\Operations\emit($op);
        $this->assertStringContainsString('* Summary', $emitted);
        $this->assertStringContainsString('* Desc line 1', $emitted);
        $this->assertStringContainsString('* @tags Tag1,Tag2', $emitted);
        $this->assertStringContainsString('* @externalDocs http://example.com Example Docs', $emitted);
        $this->assertStringContainsString('* @oas-callback myCb []', $emitted);
        $this->assertStringContainsString('* @oas-link 200 link1 {"operationId":"otherOp"}', $emitted);
        $this->assertStringContainsString('public function fullOp(string $id, ?object $body): int', $emitted);

        $opRef = [
            'operationId' => 'refOp',
            'responses' => [
                '200' => [
                    'description' => 'OK',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/User',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $emittedRef = \Cdd\Operations\emit($opRef);
        $this->assertStringContainsString('public function refOp(): User', $emittedRef);
    }

    /**
     * Tests emitting DAO-backed controller method bodies for POST, DELETE, and GET list operations.
     *
     * @return void
     */
    public function testEmitPostWithSchema(): void
    {
        $op = [
            'operationId' => 'createItem',
            'responses' => [
                '200' => [
                    'description' => 'Created',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/MyItem',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $emitted = \Cdd\Operations\emit($op, '/items', 'post');
        $this->assertStringContainsString("\Api\Daos\DaoFactory::create('MyItem')", $emitted);
        $this->assertStringContainsString('$dao->create(', $emitted);

        $emittedDelete = \Cdd\Operations\emit($op, '/items/{itemId}', 'delete');
        $this->assertStringContainsString('$dao->delete($itemId)', $emittedDelete);

        $opWithSec = $op;
        $opWithSec['security'] = [['BearerAuth' => []]];
        $emittedPut = \Cdd\Operations\emit($opWithSec, '/items/{itemId}', 'put');
        $this->assertStringContainsString('$dao->update($itemId,', $emittedPut);
        $this->assertStringContainsString("\$this->requireSecurity('BearerAuth'", $emittedPut);

        $emittedGetById = \Cdd\Operations\emit($op, '/items/{itemId}', 'get');
        $this->assertStringContainsString('$dao->getById($itemId)', $emittedGetById);

        $opList = [
            'operationId' => 'listItems',
            'responses' => [
                '200' => [
                    'description' => 'List',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/MyItem'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $emittedList = \Cdd\Operations\emit($opList, '/items', 'get');
        $this->assertStringContainsString('$dao->getAll()', $emittedList);
    }

    /**
     * Tests validation errors for Operation Objects.
     *
     * @return void
     */
    public function testValidateOperationObjectErrors(): void
    {
        $tests = [
            ['input' => 'string', 'error' => 'Operation must be an object'],
            ['input' => ['parameters' => [['in' => 'query'], ['in' => 'querystring']]], 'error' => 'query and querystring parameters are mutually exclusive'],
            ['input' => ['parameters' => [['in' => 'querystring'], ['in' => 'querystring']]], 'error' => 'querystring parameter MUST NOT appear more than once'],
            ['input' => ['tags' => 123], 'error' => 'Operation "tags" must be an array of strings'],
            ['input' => ['tags' => [123]], 'error' => 'Operation "tags" items must be strings'],
            ['input' => ['summary' => 123], 'error' => 'Operation "summary" must be a string'],
            ['input' => ['description' => 123], 'error' => 'Operation "description" must be a string'],
            ['input' => ['operationId' => 123], 'error' => 'Operation "operationId" must be a string'],
            ['input' => ['parameters' => 123], 'error' => 'Operation "parameters" must be an array'],
            ['input' => [], 'error' => 'Operation must contain a "responses" object'],
            ['input' => ['responses' => 123], 'error' => 'Operation "responses" must be a Responses Object map'],
            ['input' => ['responses' => []], 'error' => 'Responses Object MUST contain at least one response code'],
            ['input' => ['responses' => ['invalid' => []]], 'error' => 'Responses keys must be HTTP status codes, ranges like 2XX, or "default"'],
            ['input' => ['responses' => ['200' => ['description' => 'OK']], 'callbacks' => 123], 'error' => 'Operation "callbacks" must be a map'],
            ['input' => ['responses' => ['200' => ['description' => 'OK']], 'deprecated' => 123], 'error' => 'Operation "deprecated" must be a boolean'],
            ['input' => ['responses' => ['200' => ['description' => 'OK']], 'security' => 123], 'error' => 'Operation "security" must be an array'],
            ['input' => ['responses' => ['200' => ['description' => 'OK']], 'servers' => 123], 'error' => 'Operation "servers" must be an array'],
        ];

        foreach ($tests as $test) {
            $this->assertThrows(function () use ($test): void {
                \Cdd\Operations\validateOperationObject($test['input']);
            }, \RuntimeException::class, $test['error']);
        }

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Operations\validateOperationObject([
                'responses' => ['200' => ['description' => 'OK']],
                'tags' => ['valid'],
            ]);
        });
    }

    /**
     * Tests validating Callback or Reference Objects.
     *
     * @return void
     */
    public function testValidateCallbackOrReferenceObject(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Operations\validateCallbackOrReferenceObject('not array');
        }, \RuntimeException::class, 'Callback must be an object');

        $this->assertDoesNotThrow(function (): void {
            \Cdd\Operations\validateCallbackOrReferenceObject(['$ref' => '#/components/callbacks/MyCb']);
            \Cdd\Operations\validateCallbackOrReferenceObject([
                'x-ignore' => 123,
                'http://example.com' => [],
            ]);
        });
    }

    /**
     * Tests `\Cdd\Operations\parse` with optional parameters and requestBody.
     *
     * @return void
     */
    public function testParseOptionals(): void
    {
        $op = \Cdd\Operations\parse('testOp', [['in' => 'query', 'name' => 'q']], [], ['content' => []]);
        $this->assertTrue(isset($op['parameters']));
        $this->assertTrue(isset($op['requestBody']));
    }

    /**
     * Tests validating optional fields on an Operation Object.
     *
     * @return void
     */
    public function testValidateOperationObjectOptionals(): void
    {
        $op = [
            'operationId' => 'testOp',
            'responses' => [
                '200' => ['description' => 'OK'],
                'x-test' => ['description' => 'Test'],
                '5XX' => ['description' => 'Server Error'],
            ],
            'parameters' => [
                ['$ref' => '#/components/parameters/Test'],
            ],
            'externalDocs' => ['url' => 'http://example.com'],
            'servers' => [['url' => 'http://example.com']],
            'callbacks' => [
                'onData' => [
                    '{$request.query.url}' => [
                        'post' => ['responses' => ['200' => ['description' => 'OK']]],
                    ],
                ],
            ],
            'security' => [
                ['basicAuth' => []],
            ],
        ];
        $seen = [];
        \Cdd\Operations\validateOperationObject($op, [], $seen);
        $this->assertArrayHasKey('testOp', $seen);
    }

    /**
     * Tests validation error when Operation `servers` is not an array.
     *
     * @return void
     */
    public function testValidateOperationServersNotArray(): void
    {
        $this->assertThrows(function (): void {
            \Cdd\Operations\validateOperationObject(['responses' => ['200' => ['description' => 'OK']], 'servers' => 'not array']);
        }, \RuntimeException::class, 'Operation "servers" must be an array');
    }
}
