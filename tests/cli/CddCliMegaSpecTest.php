<?php

/**
 * Integration test for `CddCli` across `to_sdk_cli`, `to_sdk`, `to_server`, and `to_docs_json`.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests generating SDK CLI, SDK, Server, and Docs JSON from a comprehensive spec.
 */
class CddCliMegaSpecTest extends TestCase
{
    /**
     * Tests `from_openapi` and `to_docs_json` generation targets.
     *
     * @return void
     */
    public function testMegaSpec(): void
    {
        $tmpBase = sys_get_temp_dir() . '/cdd_megaspec_' . uniqid();
        mkdir($tmpBase, 0777, true);
        $specFile = "$tmpBase/mega.json";

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
                        'required' => ['str'],
                    ],
                ],
            ],
            'paths' => [
                '/test' => [
                    'post' => [
                        'operationId' => 'testId',
                        'description' => 'A test operation',
                        'parameters' => [
                            ['name' => 'myParam', 'in' => 'query', 'required' => true, 'description' => 'A param', 'schema' => ['type' => 'string']],
                        ],
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Obj',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'ok'],
                        ],
                    ],
                ],
            ],
        ];
        file_put_contents($specFile, (string) json_encode($spec));

        try {
            ob_start();
            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_sdk_cli', '-i', $specFile, '-o', "$tmpBase/mega_1"]));
            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_sdk', '-i', $specFile, '-o', "$tmpBase/mega_2"]));
            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_server', '-i', $specFile, '-o', "$tmpBase/mega_3"]));
            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $specFile, '-o', "$tmpBase/mega_4.json", '--no-imports', '--no-wrapping']));
            ob_end_clean();

            $this->assertTrue(file_exists("$tmpBase/mega_1/src/api_cli.php"));
            $this->assertTrue(file_exists("$tmpBase/mega_2/src/ApiClient.php"));
            $this->assertTrue(file_exists("$tmpBase/mega_3/src/routes.php"));
            $this->assertTrue(file_exists("$tmpBase/mega_4.json"));
        } finally {
            $this->removeDir($tmpBase);
        }
    }
}
