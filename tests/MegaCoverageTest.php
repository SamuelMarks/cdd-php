<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class MegaCoverageTest extends TestCase
{
    public function testCoverageBoost(): void
    {
        $spec = __DIR__ . '/../cdd-openapi-test-harness/petstore.json';
        if (!file_exists($spec)) {
            $spec = __DIR__ . '/../../cdd-openapi-test-harness/petstore.json';
        }
        if (!file_exists($spec)) {
            $spec = __DIR__ . '/../../petstore.json';
        }

        $tmpBase = sys_get_temp_dir() . '/cdd_mega_cov_' . uniqid();
        mkdir($tmpBase, 0777, true);

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
            exec("rm -rf " . escapeshellarg($tmpBase));
        }
    }
}
