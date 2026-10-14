<?php

/**
 * Parses PHP docblock comments into structured arrays.
 *
 * @package Cdd\Docstrings
 */

declare(strict_types=1);

namespace Cdd\Docstrings;

/**
 * Parses a PHP docstring into its component parts.
 *
 * @param string $docComment The raw PHP docblock string.
 * @return array{description: string, tags: array<string, mixed>} Parsed description and tags.
 */
function parse(string $docComment): array
{
    $parsed = [
        'description' => '',
        'tags' => [],
    ];

    if ($docComment === '') {
        return $parsed;
    }

    $lines = explode("
", $docComment);
    $descriptionLines = [];

    foreach ($lines as $line) {
        $line = trim($line);
        $line = (string) preg_replace('/^\/?\**\/? ?/', '', $line);

        if (str_starts_with($line, '@')) {
            $parts = preg_split('/\s+/', $line, 3);
            $tag = substr((string) ($parts[0] ?? ''), 1);
            $type = $parts[1] ?? '';
            $desc = $parts[2] ?? '';

            if ($tag === 'param') {
                $parsed['tags'][$tag][] = ['type' => $type, 'name' => $desc];
            } elseif ($tag === 'return') {
                $parsed['tags'][$tag] = ['type' => $type, 'description' => $desc];
            } else {
                $parsed['tags'][$tag][] = $type . ' ' . $desc;
            }
        } elseif ($line !== '') {
            $descriptionLines[] = $line;
        }
    }

    $parsed['description'] = implode("
", $descriptionLines);
    return $parsed;
}
