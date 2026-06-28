<?php

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Tests\Framework\TestCase;
use Cdd\Cli\CddConfig;

class CddConfigTest extends TestCase
{
    public function testToArgsAllOptions()
    {
        $config = new CddConfig();
        $config->command = 'from_openapi';
        $config->target = 'to_sdk';
        $config->input = 'spec.json';
        $config->output = 'out';
        $config->noGithubActions = true;
        $config->noInstallablePackage = true;
        $config->tests = true;
        $config->mcp = true;
        $config->noImports = true;
        $config->noWrapping = true;
        $config->truth = 'class';

        $args = $config->toArgs();
        $this->assertEquals([
            'cdd-php',
            'from_openapi',
            'to_sdk',
            '-i', 'spec.json',
            '-o', 'out',
            '--no-github-actions',
            '--no-installable-package',
            '--tests',
            '--mcp',
            '--no-imports',
            '--no-wrapping',
            '-t', 'class'
        ], $args);
    }

    public function testToArgsMinimal()
    {
        $config = new CddConfig();
        $args = $config->toArgs();
        $this->assertEquals(['cdd-php'], $args);
    }
}
