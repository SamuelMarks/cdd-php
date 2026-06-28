<?php

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * CddConfig
 *
 * Typed configuration struct for programmatic SDK invocation.
 */
class CddConfig
{
    public string $command = '';
    public string $target = '';
    public string $input = '';
    public string $output = '';
    public bool $noGithubActions = false;
    public bool $noInstallablePackage = false;
    public bool $tests = false;
    public bool $mcp = false;
    public bool $noImports = false;
    public bool $noWrapping = false;
    public string $truth = '';

    /**
     * Converts the configuration into an array of command-line arguments.
     *
     * @return array<string>
     */
    public function toArgs(): array
    {
        $args = ['cdd-php'];

        if ($this->command !== '') {
            $args[] = $this->command;
        }

        if ($this->command === 'from_openapi' && $this->target !== '') {
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
