<?php

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
     * Serve JSON-RPC over stdio.
     *
     * @param array $argv The command line arguments
     * @param int $argc The number of arguments
     * @return int The exit status code
     */
    public static function serveJsonRpc(array $argv, int $argc): int
    {
        // Use shell execution of bin/cdd-php to prevent rewriting the whole CLI router?
        // No, the task requires programmatic SDK: "Extract the execution logic into a dedicated module/class level method (Cdd\Cli) that can be required or instantiated."
        // We will move the routing logic here.
        return 0;
    }
}
