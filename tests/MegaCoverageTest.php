<?php

/**
 * End-to-end CLI subcommand tests using a self-contained OpenAPI fixture.
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
     * Builds the Petstore OpenAPI 3.2.0 test fixture.
     *
     * @param string $path Destination file path.
     * @return void
     */
    private function writePetstoreFixture(string $path): void
    {
        file_put_contents($path, (string) json_encode([
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
    }

    /**
     * Tests `CddCli::run` across help, version, and invalid subcommand error handling.
     *
     * @return void
     */
    public function testCliHelpVersionAndErrorSubcommands(): void
    {
        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php']));
        $helpOut = (string) ob_get_clean();
        $this->assertStringContainsString('cdd-php CLI', $helpOut);

        ob_start();
        $this->assertEquals(0, CddCli::run(['cdd-php', '--help']));
        $this->assertEquals(0, CddCli::run(['cdd-php', '-h']));
        $this->assertEquals(0, CddCli::run(['cdd-php', '--version']));
        $this->assertEquals(0, CddCli::run(['cdd-php', '-v']));
        $verOut = (string) ob_get_clean();
        $this->assertStringContainsString('0.0.4', $verOut);

        ob_start();
        $this->assertEquals(1, CddCli::run(['cdd-php', 'unknown_command']));
        $this->assertEquals(1, CddCli::run(['cdd-php', 'from_openapi', 'unknown']));
        $this->assertEquals(1, CddCli::run(['cdd-php', 'to_docs_json']));
        ob_end_clean();
    }

    /**
     * Tests `to_docs_json`, `from_openapi` (`to_sdk_cli`, `to_sdk`, `to_server`), and `serve_json_rpc` output contents.
     *
     * @return void
     */
    public function testEndToEndProjectGenerationAndContents(): void
    {
        $tmpBase = sys_get_temp_dir() . '/cdd_mega_cov_' . uniqid();
        mkdir($tmpBase, 0777, true);
        $spec = "$tmpBase/petstore.json";
        $this->writePetstoreFixture($spec);

        try {
            ob_start();
            $this->assertEquals(0, CddCli::run(['cdd-php', 'to_docs_json', '-i', $spec, '-o', "$tmpBase/docs.json", '--no-imports', '--no-wrapping']));
            $this->assertTrue(file_exists("$tmpBase/docs.json"));
            $docsDecoded = json_decode((string) file_get_contents("$tmpBase/docs.json"), true);
            $this->assertEquals('php', $docsDecoded[0]['language'] ?? '');
            $this->assertEquals('listPets', $docsDecoded[0]['operations'][0]['operationId'] ?? '');
            $this->assertFalse(isset($docsDecoded[0]['operations'][0]['code']['imports']));
            $this->assertFalse(isset($docsDecoded[0]['operations'][0]['code']['wrapper_start']));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_sdk_cli', '-i', $spec, '-o', "$tmpBase/sdk_cli", '--no-github-actions', '--no-installable-package', '--tests', '--mcp']));
            $this->assertTrue(file_exists("$tmpBase/sdk_cli/src/api_cli.php"));
            $cliContent = (string) file_get_contents("$tmpBase/sdk_cli/src/api_cli.php");
            $this->assertStringContainsString("if (\$command === 'list_pets')", $cliContent);
            $this->assertStringContainsString("\$client->listPets(", $cliContent);

            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_sdk', '-i', $spec, '-o', "$tmpBase/sdk", '--no-github-actions', '--no-installable-package', '--tests', '--mcp']));
            $this->assertTrue(file_exists("$tmpBase/sdk/src/ApiClient.php"));
            $sdkContent = (string) file_get_contents("$tmpBase/sdk/src/ApiClient.php");
            $this->assertStringContainsString('public function listPets(', $sdkContent);
            $this->assertStringContainsString('public function connect_mcp(', $sdkContent);

            $this->assertEquals(0, CddCli::run(['cdd-php', 'from_openapi', 'to_server', '-i', $spec, '-o', "$tmpBase/server"]));
            $this->assertTrue(file_exists("$tmpBase/server/src/routes.php"));
            $this->assertTrue(file_exists("$tmpBase/server/src/Routes/PetsRoutes.php"));
            $routeFileContent = (string) file_get_contents("$tmpBase/server/src/Routes/PetsRoutes.php");
            $this->assertStringContainsString("Route::get('/pets'", $routeFileContent);
            $this->assertTrue(file_exists("$tmpBase/server/src/Models/Pet.php"));

            $this->assertEquals(0, CddCli::run(['cdd-php', 'serve_json_rpc', '--port', '0', '--listen', '127.0.0.1', '--timeout', '0.01']));
            ob_end_clean();
        } finally {
            $this->removeDir($tmpBase);
        }
    }
}
