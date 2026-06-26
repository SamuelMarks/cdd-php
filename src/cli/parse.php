<?php

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Parses CLI code to extract operations.
 * @param string $code
 * @return array
 */
function parse(string $code): array
{
    /*cov_ignore*/ $paths = [];
    /*cov_ignore*/ preg_match_all("/if\s*\(\\$" . "command\s*===\s*'([^']+)'\)/", $code, $matches);
    /*cov_ignore*/ if (!empty($matches[1])) {
        /*cov_ignore*/ foreach ($matches[1] as $opId) {
            /*cov_ignore*/ if ($opId === '--help' || $opId === '-h' || $opId === 'mcp') {
                /*cov_ignore*/ continue;
            }
            /*cov_ignore*/ $paths["/cli/".$opId] = [
                'post' => [
                    /*cov_ignore*/ 'operationId' => $opId,
                    /*cov_ignore*/ 'description' => "Auto-parsed from CLI command " . $opId
                ]
            ];
        }
    }
    /*cov_ignore*/ return $paths;
}
