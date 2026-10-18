<?php

/**
 * Typed configuration object for programmatic CddCli invocation.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * CddConfig
 *
 * Typed configuration struct for programmatic SDK invocation.
 */
class CddConfig
{
    /**
     * @var string The primary CLI command (e.g. 'from_openapi', 'to_openapi', 'sync', 'to_docs_json', 'mcp', 'serve_json_rpc').
     */
    public string $command = '';

    /**
     * @var string The subcommand target for 'from_openapi' (e.g. 'to_sdk', 'to_sdk_cli', 'to_server').
     */
    public string $target = '';

    /**
     * @var string Input file or directory path.
     */
    public string $input = '';

    /**
     * @var string Output file or directory path.
     */
    public string $output = '';

    /**
     * @var bool Whether to skip generating GitHub Actions workflow files.
     */
    public bool $noGithubActions = false;

    /**
     * @var bool Whether to skip generating composer.json package metadata.
     */
    public bool $noInstallablePackage = false;

    /**
     * @var bool Whether to generate PHPUnit test suites.
     */
    public bool $tests = false;

    /**
     * @var bool Whether to generate MCP server/client endpoints.
     */
    public bool $mcp = false;

    /**
     * @var bool Whether to omit require/import lines in `to_docs_json`.
     */
    public bool $noImports = false;

    /**
     * @var bool Whether to omit wrapper code in `to_docs_json`.
     */
    public bool $noWrapping = false;

    /**
     * @var string Source of truth for `sync` ('class' or 'spec').
     */
    public string $truth = '';

    /**
     * Converts the configuration into an array of command-line arguments.
     *
     * @return array<int, string> Command-line arguments list.
     */
    public function toArgs(): array
    {
        $args = ['cdd-php'];

        if ($this->command !== '') {
            $args[] = $this->command;
        }

        if (($this->command === 'from_openapi' || $this->command === 'from_arazzo') && $this->target !== '') {
            $args[] = $this->target;
        }

        if ($this->input !== '') {
            $args[] = '-i';
            $args[] = $this->input;
        }

        if ($this->output !== '') {
            $args[] = '-o';
            $args[] = $this->output;
        }

        if ($this->noGithubActions) {
            $args[] = '--no-github-actions';
        }

        if ($this->noInstallablePackage) {
            $args[] = '--no-installable-package';
        }

        if ($this->tests) {
            $args[] = '--tests';
        }

        if ($this->mcp) {
            $args[] = '--mcp';
        }

        if ($this->noImports) {
            $args[] = '--no-imports';
        }

        if ($this->noWrapping) {
            $args[] = '--no-wrapping';
        }

        if ($this->truth !== '') {
            $args[] = '-t';
            $args[] = $this->truth;
        }

        return $args;
    }
}
