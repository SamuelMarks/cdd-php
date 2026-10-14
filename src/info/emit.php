<?php

/**
 * Emits PHP docblock comments from OpenAPI Info Objects.
 *
 * @package Cdd\Info
 */

declare(strict_types=1);

namespace Cdd\Info;

/**
 * Emits PHP comment block from an OpenAPI Info Object.
 *
 * @param array<string, mixed> $info The OpenAPI Info Object.
 * @return string The formatted PHP docblock comment.
 */
function emit(array $info): string
{
    $title = (string) ($info['title'] ?? 'API');
    $version = (string) ($info['version'] ?? '1.0.0');
    $description = (string) ($info['description'] ?? '');

    $out = "/**
 * $title (v$version)
";
    if ($description !== '') {
        $out .= " *
";
        foreach (explode("
", $description) as $line) {
            $out .= " * $line
";
        }
    }
    $out .= " */
";
    return $out;
}
