<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

class ParseEmitTest extends TestCase
{
    public function testToOpenApi()
    {
        $dir = sys_get_temp_dir() . '/cdd_to_openapi_test_' . uniqid();
        mkdir($dir);
        mkdir("$dir/src");

        file_put_contents("$dir/src/Models.php", "<?php\nclass User { public int \$id; public string \$name; }\n");
        file_put_contents("$dir/src/routes.php", "<?php\n/**\n * @return User\n */\nfunction getUser() {}\n");

        ob_start();
        \Cdd\Cli\CddCli::run(['cdd-php', 'to_openapi', '-i', "$dir/src/Models.php", '-o', "$dir/openapi.json"]);
        $out = ob_get_clean();

        $this->assertTrue(strpos($out, 'Emitted OpenAPI to') !== false);
        $this->assertTrue(file_exists("$dir/openapi.json"));

        $json = json_decode(file_get_contents("$dir/openapi.json"), true);
        $this->assertTrue(isset($json['components']['schemas']['User']));

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
