<?php

/**
 * Tests for `CddConfig` argument serialization.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Cli\CddConfig;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests `CddConfig::toArgs` with all options and minimal defaults.
 */
class CddConfigTest extends TestCase
{
    /**
     * Tests `CddConfig::toArgs` when all properties are populated.
     *
     * @return void
     */
    public function testToArgsAllOptions(): void
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
            '-t', 'class',
        ], $args);
    }

    /**
     * Tests `CddConfig::toArgs` with default values.
     *
     * @return void
     */
    public function testToArgsMinimal(): void
    {
        $config = new CddConfig();
        $args = $config->toArgs();
        $this->assertEquals(['cdd-php'], $args);
    }
}
