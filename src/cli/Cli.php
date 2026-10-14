<?php

/**
 * Core CLI application wrapper for programmatic and JSON-RPC execution.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Application
 *
 * Core application logic for the CLI.
 */
class Application
{
    /**
     * Serve JSON-RPC or execute CLI commands programmatically.
     *
     * @param array<int, string> $argv The command line arguments.
     * @param int $argc The number of arguments.
     * @return int The exit status code.
     */
    public static function serveJsonRpc(array $argv, int $argc): int
    {
        if ($argc <= 1) {
            return 0;
        }
        return CddCli::run($argv);
    }
}
