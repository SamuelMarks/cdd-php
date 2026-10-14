<?php

/**
 * Parses generated PHP CLI scripts to extract command operations.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

/**
 * Parses CLI code to extract operations.
 *
 * @param string $code The PHP CLI source code.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Paths map.
 */
function parse(string $code): array
{
    $paths = [];
    preg_match_all('/if\s*\([\x24]command\s*===\s*\'([^\']+)\'\)/', $code, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $opId) {
            if ($opId === '--help' || $opId === '-h' || $opId === 'mcp') {
                continue;
            }
            $paths["/cli/" . $opId] = [
                'post' => [
                    'operationId' => $opId,
                    'description' => "Auto-parsed from CLI command " . $opId,
                ],
            ];
        }
    }
    return $paths;
}
