<?php

declare(strict_types=1);

namespace Cdd\Info;

/**
 * Emits PHP comment block from an OpenAPI Info Object.
 */
function emit(array $info): string {
    $title = $info['title'] ?? 'API';
    $version = $info['version'] ?? '1.0.0';
    $description = $info['description'] ?? '';
    
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
