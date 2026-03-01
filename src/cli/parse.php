<?php

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Parses CLI code to extract operations.
 * @param string $code
 * @return array
 */
function parse(string $code): array {
    $paths = [];
    preg_match_all("/if\s*\(\\$" . "command\s*===\s*'([^']+)'\)/", $code, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $opId) {
            if ($opId === '--help' || $opId === '-h') continue;
            $paths["/cli/".$opId] = [
                'post' => [
                    'operationId' => $opId,
                    'description' => "Auto-parsed from CLI command " . $opId
                ]
            ];
        }
    }
    return $paths;
}