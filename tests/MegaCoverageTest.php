<?php

/**
 * End-to-end CLI subcommand coverage tests using a self-contained OpenAPI fixture.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Cli\CddCli;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests CLI help, version, error handling, and full project generation modes.
 */
class MegaCoverageTest extends TestCase
{
    /**
     * Tests `CddCli::run` across help, version, `to_docs_json`, `from_openapi`, and `serve_json_rpc`.
     *
     * @return void
     */
    public function testCoverageBoost(): void
    {
        $tmpBase = sys_get_temp_dir() . '/cdd_mega_cov_' . uniqid();
        mkdir($tmpBase, 0777, true);
        $spec = "$tmpBase/petstore.json";
        file_put_contents($spec, (string) json_encode([
            'openapi' => '3.2.0',
            'info' => ['title' => 'Petstore Fixture', 'version' => '1.0.0'],
            'paths' => [
                '/pets' => [
                    'get' => [
                        'operationId' => 'listPets',
                        'parameters' => [
                            ['name' => 'limit', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'A paged array of pets',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Pet']]],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Pet' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]));

        try {
            ob_start();
            $this->assertEquals(0, CddCli::run(['cdd-php']));
            $this->assertEquals(0, CddCli::run(['cdd-php', '--help']));
            $this->assertEquals(0, CddCli::run(['cdd-php', '-h']));
            $this->assertEquals(0, CddCli::run(['cdd-php', '--version']));
            $this->assertEquals(0, CddCli::run(['cdd-php', '-v']));
            $this->assertEquals(1, CddCli::run(['cdd-php', 'unknown_command']));
            $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', 'unknown']));
            $this->assertEquals(1, CddCli::run(['cdd-php', 'to_docs_json']));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $spec, '-o', "$tmpBase/docs.json", '--no-imports', '--no-wrapping']));
            $this->assertTrue(file_exists("$tmpBase/docs.json"));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_sdk_cli', '-i', $spec, '-o', "$tmpBase/sdk_cli", '--no-github-actions', '--no-installable-package', '--tests', '--mcp']));
            $this->assertTrue(file_exists("$tmpBase/sdk_cli/src/api_cli.php"));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_sdk', '-i', $spec, '-o', "$tmpBase/sdk", '--no-github-actions', '--no-installable-package', '--tests', '--mcp']));
            $this->assertTrue(file_exists("$tmpBase/sdk/src/ApiClient.php"));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_server', '-i', $spec, '-o', "$tmpBase/server"]));
            $this->assertTrue(file_exists("$tmpBase/server/src/routes.php"));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'serve_json_rpc', '--port', '0', '--listen', '127.0.0.1', '--timeout', '0.01']));
            ob_end_clean();
        } finally {
            $this->removeDir($tmpBase);
        }
    }
}
