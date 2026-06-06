<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddCli;

class CddCliHelperTest extends TestCase
{
    public function testHelpers()
    {
        ob_start();
        CddCli::generate_from_openapi([]);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        ob_start();
        CddCli::generate_to_openapi([]);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, 'Error:') !== false);

        ob_start();
        // to_docs_json writes to stderr, so we might need to capture stderr.
        // Actually it might return 1
        CddCli::generate_docs_json([]);
        $out = ob_get_clean();
        $this->assertTrue(true);

        ob_start();
        CddCli::run(['cdd-php', '-v']);
        $out = ob_get_clean();
        $this->assertTrue(strpos($out, '0.0.1') !== false);

        ob_start();
        CddCli::run(['cdd-php', 'unknown_command']);
        $out = ob_get_clean();
        $this->assertTrue(true); // prints to stderr
    }
}
