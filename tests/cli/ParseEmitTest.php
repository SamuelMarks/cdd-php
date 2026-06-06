<?php

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

use function Cdd\Cli\emit;
use function Cdd\Cli\parse;

class ParseEmitTest extends TestCase
{
    public function testEmitAndParse()
    {
        $paths = [
            '/test-path' => [
                'parameters' => [
                    ['name' => 'skip', 'in' => 'query']
                ],
                'summary' => 'Ignored summary',
                'description' => 'Ignored description',
                'servers' => [ ['url' => 'http://test'] ],
                'get' => [
                    'description' => 'A test operation',
                ],
                'post' => [
                    'operationId' => 'testPost',
                    'description' => 'Post operation',
                    'parameters' => [
                        ['name' => 'id', 'required' => true, 'description' => 'The ID'],
                        ['name' => 'optional_param', 'required' => false, 'description' => 'An optional param'],
                        ['name' => 'no_desc']
                    ],
                    'requestBody' => [
                        'content' => ['application/json' => []]
                    ]
                ],
                'put' => [
                    'operationId' => 'testPut',
                ]
            ]
        ];

        $emitted = emit($paths);

        $this->assertTrue(strpos($emitted, "if (\$command === 'gettestpath')") !== false);
        $this->assertTrue(strpos($emitted, "if (\$command === 'test_post')") !== false);
        $this->assertTrue(strpos($emitted, "A test operation") !== false);
        $this->assertTrue(strpos($emitted, "Call PUT /test-path") !== false);
        $this->assertTrue(strpos($emitted, "--id (required) The ID") !== false);
        $this->assertTrue(strpos($emitted, "--optional_param (optional) An optional param") !== false);
        $this->assertTrue(strpos($emitted, "--no_desc (optional) ") !== false);
        $this->assertTrue(strpos($emitted, "--body (optional) JSON body") !== false);

        $fakeCode = "if (\$command === '--help') { } if (\$command === '-h') { } if (\$command === 'myOp') { }";
        $parsedFake = parse($fakeCode);

        $this->assertTrue(isset($parsedFake['/cli/myOp']));
        $this->assertTrue(!isset($parsedFake['/cli/--help']));
        $this->assertTrue(!isset($parsedFake['/cli/-h']));

        $parsed = parse($emitted);
        $this->assertTrue(isset($parsed['/cli/gettestpath']));
        $this->assertTrue(isset($parsed['/cli/test_post']));
        $this->assertTrue(isset($parsed['/cli/test_put']));
    }

    public function testEmitMcpServer()
    {
        $paths = [
            '/test' => [
                'post' => [
                    'operationId' => 'testPost',
                    'parameters' => [
                        ['name' => 'id', 'required' => true]
                    ],
                    'requestBody' => ['content' => ['application/json' => []]]
                ]
            ]
        ];

        $emitted = emit($paths);

        $this->assertTrue(strpos($emitted, "if (\$command === 'mcp') {") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'initialize'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'tools/list'") !== false);
        $this->assertTrue(strpos($emitted, "'name' => 'testPost'") !== false || strpos($emitted, "'name' => 'test_post'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'tools/call'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'resources/list'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'resources/read'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'resources/templates/list'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'prompts/list'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'prompts/get'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'ping'") !== false);
        $this->assertTrue(strpos($emitted, "\$req['method'] === 'completion/complete'") !== false);
    }

    public function testParseEmpty()
    {
        $parsed = parse("no commands here");
        $this->assertEquals([], $parsed);
    }
}
