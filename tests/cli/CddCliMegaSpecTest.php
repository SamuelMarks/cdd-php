<?php

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class CddCliMegaSpecTest extends TestCase
{
    public function testMegaSpec()
    {
        $spec = [
            'openapi' => '3.2.0',
            'info' => ['title' => 'Mega', 'version' => '1'],
            'servers' => [['url' => 'http://localhost']],
            'components' => [
                'schemas' => [
                    'Obj' => [
                        'type' => 'object',
                        'properties' => [
                            'str' => ['type' => 'string'],
                        ],
                        'required' => ['str']
                    ]
                ]
            ],
            'paths' => [
                '/test' => [
                    'post' => [
                        'operationId' => 'testId',
                        'description' => 'A test operation',
                        'parameters' => [
                            ['name' => 'myParam', 'in' => 'query', 'required' => true, 'description' => 'A param', 'schema' => ['type' => 'string']]
                        ],
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Obj'
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'ok']
                        ]
                    ]
                ]
            ]
        ];
        file_put_contents('mega.json', json_encode($spec));

        echo "Starting to_sdk_cli\n";
        CddCli::run(['cdd-php', 'from_openapi', 'to_sdk_cli', '-i', 'mega.json', '-o', sys_get_temp_dir() . '/cdd_mega_1']);
        echo "Starting to_sdk\n";
        CddCli::run(['cdd-php', 'from_openapi', 'to_sdk', '-i', 'mega.json', '-o', sys_get_temp_dir() . '/cdd_mega_2']);
        echo "Starting to_server\n";
        CddCli::run(['cdd-php', 'from_openapi', 'to_server', '-i', 'mega.json', '-o', sys_get_temp_dir() . '/cdd_mega_3']);
        echo "Starting to_docs_json\n";
        CddCli::run(['cdd-php', 'to_docs_json', '-i', 'mega.json', '-o', sys_get_temp_dir() . '/cdd_mega_4.json', '--no-imports', '--no-wrapping']);
        echo "Done Mega\n";

        unlink('mega.json');
        $this->assertTrue(true);
    }
}
