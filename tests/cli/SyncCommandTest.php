<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

class SyncCommandTest extends TestCase
{
    public function testSync()
    {
        $dir = sys_get_temp_dir() . '/cdd_sync_test_' . uniqid();
        mkdir($dir);
        mkdir("$dir/src");

        file_put_contents("$dir/src/Models.php", "<?php\nclass User { public int \$id; public string \$name; }\n");
        file_put_contents("$dir/src/routes.php", "<?php\n/**\n * @return User\n */\nfunction getUser() {}\n");

        ob_start();
        \Cdd\Cli\CddCli::run(['cdd-php', 'sync', '-d', $dir]);
        $out = ob_get_clean();

        $this->assertTrue(strpos($out, 'Synchronized codebase in') !== false);
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
