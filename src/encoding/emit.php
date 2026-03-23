<?php

declare(strict_types=1);

namespace Cdd\Encoding;

/**
 * Emits PHP code representations for an Encoding Object.
 * 
 * @param array $encoding The Encoding object array.
 * @return string The PHP string representation.
 */
function emit(array $encoding): string {
    $out = "/* Encoding object emitted */\n";
    if (isset($encoding['contentType'])) {
        $out .= "// Content-Type: " . $encoding['contentType'] . "\n";
    }
    if (isset($encoding['headers'])) {
        foreach ($encoding['headers'] as $name => $header) {
            $out .= "// Header: {$name}\n";
        }
    }
    if (isset($encoding['style'])) {
        $out .= "// Style: " . $encoding['style'] . "\n";
    }
    if (isset($encoding['explode'])) {
        $out .= "// Explode: " . ($encoding['explode'] ? 'true' : 'false') . "\n";
    }
    if (isset($encoding['allowReserved'])) {
        $out .= "// AllowReserved: " . ($encoding['allowReserved'] ? 'true' : 'false') . "\n";
    }
    return $out;
}
