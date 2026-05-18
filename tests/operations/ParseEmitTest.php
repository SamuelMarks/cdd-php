<?php

declare(strict_types=1);

namespace Cdd\Tests\Operations;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function setUp(): void
    {
        global $globalOperationIds;
        $globalOperationIds = [];
    }

    public function tearDown(): void
    {
        global $globalOperationIds;
        $globalOperationIds = [];
    }

    public function testParseAndEmit()
    {
        $op = \Cdd\Operations\parse('getUser', [], [], null, 'Get a user');
        $this->assertEquals('getUser', $op['operationId']);
        $this->assertEquals('Get a user', $op['summary']);
        $this->assertEquals('Success', $op['responses']['200']['description']);

        $emitted = \Cdd\Operations\emit($op);
        $this->assertTrue(strpos($emitted, 'public function getUser()') !== false);
        $this->assertTrue(strpos($emitted, '* Get a user') !== false);
    }

    public function testEmitFullCoverage()
    {
        $op = [
            'operationId' => 'fullOp',
            'summary' => 'Summary',
            'description' => "Desc line 1\nDesc line 2",
            'tags' => ['Tag1', 'Tag2'],
            'externalDocs' => ['url' => 'http://example.com', 'description' => 'Example Docs'],
            'callbacks' => ['myCb' => []],
            'responses' => [
                '200' => [
                    'description' => 'Success',
                    'links' => [
                        'link1' => ['operationId' => 'otherOp']
                    ],
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            'parameters' => [
                ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]
            ],
            'requestBody' => [
                'content' => ['application/json' => ['schema' => ['type' => 'object']]]
            ]
        ];

        $emitted = \Cdd\Operations\emit($op);
        $this->assertStringContainsString('* Summary', $emitted);
        $this->assertStringContainsString('* Desc line 1', $emitted);
        $this->assertStringContainsString('* @tags Tag1,Tag2', $emitted);
        $this->assertStringContainsString('* @externalDocs http://example.com Example Docs', $emitted);
        $this->assertStringContainsString('* @oas-callback myCb []', $emitted);
        $this->assertStringContainsString('* @oas-link 200 link1 {"operationId":"otherOp"}', $emitted);
        $this->assertStringContainsString('public function fullOp(string $id, ?object $body): int', $emitted);

        // ref return type
        $opRef = [
            'operationId' => 'refOp',
            'responses' => [
                '200' => [
                    'description' => 'OK',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/User'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $emittedRef = \Cdd\Operations\emit($opRef);
        $this->assertStringContainsString('public function refOp(): User', $emittedRef);
    }

    public function testValidateOperationObjectErrors()
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
            ['input' => ['responses' => 123], 'error' => 'Operation must contain a "responses" object'],
            ['input' => ['responses' => 123], 'error' => 'Operation "responses" must be a Responses Object map', 'unset' => false],
        ];

        foreach ($tests as $test) {
            $caught = false;
            try {
                if (isset($test['unset']) && !$test['unset']) {
                    // special handling to bypass the first 'responses' check if needed, but it checks !isset first.
                    // actually if it's 123, it will fail `!isset`? No, isset(123) is true.
                    // wait, `if (!isset($operation['responses']))` will be skipped for 123.
                    // then `if (!is_array($operation['responses']))` will throw 'Operation "responses" must be a Responses Object map'.
                } elseif ($test['error'] === 'Operation must contain a "responses" object') {
                    // if we pass `['responses' => null]`
                    $test['input'] = []; // this triggers !isset
                }
                \Cdd\Operations\validateOperationObject($test['input']);
            } catch (\RuntimeException $e) {
                $this->assertEquals($test['error'], $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, "Expected exception: {$test['error']}");
        }

        $caught = false;
        try {
            \Cdd\Operations\validateOperationObject([
                'responses' => []
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Responses Object MUST contain at least one response code', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Operations\validateOperationObject([
                'responses' => ['invalid' => []]
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Responses keys must be HTTP status codes, ranges like 2XX, or "default"', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Operations\validateOperationObject([
                'responses' => ['200' => ['description' => 'OK']],
                'callbacks' => 123
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Operation "callbacks" must be a map', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Operations\validateOperationObject([
                'responses' => ['200' => ['description' => 'OK']],
                'deprecated' => 123
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Operation "deprecated" must be a boolean', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Operations\validateOperationObject([
                'responses' => ['200' => ['description' => 'OK']],
                'security' => 123
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Operation "security" must be an array', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        $caught = false;
        try {
            \Cdd\Operations\validateOperationObject([
                'responses' => ['200' => ['description' => 'OK']],
                'servers' => 123
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Operation "servers" must be an array', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testValidateCallbackOrReferenceObject()
    {
        $caught = false;
        try {
            \Cdd\Operations\validateCallbackOrReferenceObject('not array');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Callback must be an object', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);

        // ref
        \Cdd\Operations\validateCallbackOrReferenceObject(['$ref' => '#/components/callbacks/MyCb']);

        // extensions
        \Cdd\Operations\validateCallbackOrReferenceObject([
            'x-ignore' => 123,
            'http://example.com' => [] // path item
        ]);
    }

    public function testParseOptionals()
    {
        $op = \Cdd\Operations\parse('testOp', [['in' => 'query', 'name' => 'q']], [], ['content' => []]);
        $this->assertTrue(isset($op['parameters']));
        $this->assertTrue(isset($op['requestBody']));
    }

    public function testValidateOperationObjectOptionals()
    {
        // cover continue for ref/non-array param
        $op = [
            'operationId' => 'testOp',
            'responses' => ['200' => ['description' => 'OK']],
            'parameters' => [
                ['$ref' => '#/components/parameters/Test']
            ],
            'externalDocs' => ['url' => 'http://example.com'],
            'servers' => [['url' => 'http://example.com']]
        ];
        // this shouldn't throw
        \Cdd\Operations\validateOperationObject($op);
        $this->assertTrue(true);
    }
    public function testValidateOperationServersNotArray()
    {
        try {
            \Cdd\Operations\validateOperationObject(['responses' => ['200' => ['description' => 'OK']], 'servers' => 'not array']);
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Operation "servers" must be an array', $e->getMessage());
        }
    }
}
