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
                'examples' => [
                    'SampleObj' => [
                        'dataValue' => ['str' => 'hello'],
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
            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_server', '-i', $specFile, '-o', "$tmpBase/mega_3", '--tests']));
            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $specFile, '-o', "$tmpBase/mega_4.json", '--no-imports', '--no-wrapping']));
            ob_end_clean();

            $this->assertTrue(file_exists("$tmpBase/mega_1/src/api_cli.php"));
            $this->assertTrue(file_exists("$tmpBase/mega_2/src/ApiClient.php"));
            $this->assertTrue(file_exists("$tmpBase/mega_3/src/routes.php"));
            $this->assertTrue(file_exists("$tmpBase/mega_4.json"));

            // Parse generated PHP files in mega_1, mega_2, mega_3 to verify valid AST
            $parser = \Cdd\Classes\create_parser();
            $this->assertNotNull($parser->parse((string) file_get_contents("$tmpBase/mega_1/src/api_cli.php")));
            $this->assertNotNull($parser->parse((string) file_get_contents("$tmpBase/mega_2/src/ApiClient.php")));
            $this->assertNotNull($parser->parse((string) file_get_contents("$tmpBase/mega_3/src/routes.php")));
            $this->assertNotNull($parser->parse((string) file_get_contents("$tmpBase/mega_3/src/Controllers/TestIdController.php")));
            $this->assertNotNull($parser->parse((string) file_get_contents("$tmpBase/mega_3/src/Models/Obj.php")));

            // Verify docs.json content
            $docsJson = json_decode((string) file_get_contents("$tmpBase/mega_4.json"), true);
            $this->assertEquals('testId', $docsJson[0]['operations'][0]['operationId'] ?? '');
            $this->assertStringContainsString('$client->testId(', $docsJson[0]['operations'][0]['code']['snippet'] ?? '');

            // Roundtrip CLI (mega_1), SDK (mega_2), and modular Server (mega_3) back to OpenAPI via to_openapi
            ob_start();
            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_openapi', '-i', "$tmpBase/mega_1", '-o', "$tmpBase/roundtrip_cli.json"]));
            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_openapi', '-i', "$tmpBase/mega_2", '-o', "$tmpBase/roundtrip_sdk.json"]));
            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_openapi', '-i', "$tmpBase/mega_3", '-o', "$tmpBase/roundtrip_server.json"]));
            ob_end_clean();

            $cliRoundtrip = json_decode((string) file_get_contents("$tmpBase/roundtrip_cli.json"), true);
            $this->assertEquals('testId', $cliRoundtrip['paths']['/test']['post']['operationId'] ?? '');

            $sdkRoundtrip = json_decode((string) file_get_contents("$tmpBase/roundtrip_sdk.json"), true);
            $this->assertEquals('testId', $sdkRoundtrip['paths']['/test']['post']['operationId'] ?? '');
            $this->assertEquals('string', $sdkRoundtrip['components']['schemas']['Obj']['properties']['str']['type'] ?? '');
            $this->assertEquals(['str'], $sdkRoundtrip['components']['schemas']['Obj']['required'] ?? []);

            $serverRoundtrip = json_decode((string) file_get_contents("$tmpBase/roundtrip_server.json"), true);
            $this->assertEquals('testId', $serverRoundtrip['paths']['/test']['post']['operationId'] ?? '');
            $this->assertEquals('myParam', $serverRoundtrip['paths']['/test']['post']['parameters'][0]['name'] ?? '');
            $this->assertEquals('query', $serverRoundtrip['paths']['/test']['post']['parameters'][0]['in'] ?? '');
            $this->assertEquals('string', $serverRoundtrip['components']['schemas']['Obj']['properties']['str']['type'] ?? '');
            $this->assertEquals(['str'], $serverRoundtrip['components']['schemas']['Obj']['required'] ?? []);
        } finally {
            $this->removeDir($tmpBase);
        }
    }
}
