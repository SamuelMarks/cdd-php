<?php

/**
 * Tests for CLI code emission, parsing, and `to_openapi` command execution.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests CLI emission, AST syntax validity, and CLI parsing.
 */
class ParseEmitTest extends TestCase
{
    /**
     * Tests `to_openapi` CLI command and `\Cdd\Cli\emit` / `\Cdd\Cli\parse`.
     *
     * @return void
     */
    public function testToOpenApi(): void
    {
        $dir = sys_get_temp_dir() . '/cdd_to_openapi_test_' . uniqid();
        mkdir($dir);
        mkdir("$dir/src");

        file_put_contents("$dir/src/Models.php", "<?php
class User { public int \$id; public string \$name; }
");
        file_put_contents("$dir/src/routes.php", "<?php
/**
 * @return User
 */
function getUser() {}
");

        ob_start();
        \Cdd\Cli\CddCli::run(['cdd-php', 'to_openapi', '-i', "$dir/src/Models.php", '-o', "$dir/openapi.json"]);
        $out = (string) ob_get_clean();

        $this->assertTrue(strpos($out, 'Emitted OpenAPI to') !== false);
        $this->assertTrue(file_exists("$dir/openapi.json"));

        $json = json_decode((string) file_get_contents("$dir/openapi.json"), true);
        $this->assertTrue(isset($json['components']['schemas']['User']));

        $cliPaths = [
            '/users' => [
                'summary' => 'Users endpoint',
                'get' => [
                    'operationId' => 'getUsers',
                    'description' => 'List users',
                    'parameters' => [
                        ['name' => 'limit', 'in' => 'query', 'required' => true, 'description' => 'Max items'],
                    ],
                    'requestBody' => [
                        'content' => ['application/json' => ['schema' => ['type' => 'object']]],
                    ],
                ],
            ],
        ];
        $cliCode = \Cdd\Cli\emit($cliPaths);
        $this->assertStringContainsString("use Api\ApiClient;", $cliCode);
        $this->assertStringContainsString("if (\$command === 'get_users')", $cliCode);
        $this->assertStringContainsString("\$client->getUsers(\$params, \$body);", $cliCode);

        // Parse emitted CLI code with shared parser to guarantee valid PHP syntax
        $stmts = \Cdd\Classes\create_parser()->parse($cliCode);
        $this->assertNotNull($stmts);

        $cliCodeWithExisting = \Cdd\Cli\emit($cliPaths, "<?php
// Custom header
");
        $this->assertStringContainsString("// Custom header", $cliCodeWithExisting);

        $parsedCli = \Cdd\Cli\parse($cliCode);
        $this->assertArrayHasKey('/cli/get_users', $parsedCli);
        $this->assertEquals('get_users', $parsedCli['/cli/get_users']['post']['operationId']);

        // cleanup
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($dir);
    }
}
