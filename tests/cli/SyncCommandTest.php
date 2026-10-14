<?php

/**
 * Tests for the `cdd-php sync` CLI subcommand.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;

/**
 * Verifies bidirectional synchronization via `CddCli::run(['cdd-php', 'sync', ...])`.
 */
class SyncCommandTest extends TestCase
{
    /**
     * Tests synchronizing a directory containing models and routes.
     *
     * @return void
     */
    public function testSync(): void
    {
        $dir = sys_get_temp_dir() . '/cdd_sync_test_' . uniqid();
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
        \Cdd\Cli\CddCli::run(['cdd-php', 'sync', '-i', $dir]);
        $out = (string) ob_get_clean();

        $this->assertTrue(strpos($out, 'Synchronized codebase in') !== false);
        $this->assertTrue(file_exists("$dir/openapi.json"));

        ob_start();
        \Cdd\Cli\CddCli::run(['cdd-php', 'sync', '-i', $dir, '-t', 'class']);
        $out = (string) ob_get_clean();
        $this->assertTrue(strpos($out, 'Synchronized codebase in') !== false);

        $json = json_decode((string) file_get_contents("$dir/openapi.json"), true);
        $this->assertTrue(isset($json['components']['schemas']['User']));

        $this->removeDir($dir);
    }
}
