<?php

/**
 * Emits PHP docblock comments from structured arrays.
 *
 * @package Cdd\Docstrings
 */

declare(strict_types=1);

namespace Cdd\Docstrings;

/**
 * Emits a PHP docstring from an array.
 *
 * @param array<string, mixed> $doc Structured docblock definition with 'description' and 'tags'.
 * @return string The formatted PHP docblock comment.
 */
function emit(array $doc): string
{
    $out = "/**
";

    if (!empty($doc['description'])) {
        $lines = explode("
", (string) $doc['description']);
        foreach ($lines as $line) {
            $out .= " * " . $line . "
";
        }
    }

    if (!empty($doc['tags']) && is_array($doc['tags'])) {
        foreach ($doc['tags'] as $tag => $data) {
            if ($tag === 'param') {
                foreach ($data as $param) {
                    $out .= " * @param {$param['type']} {$param['name']}
";
                }
            } elseif ($tag === 'return') {
                $out .= " * @return {$data['type']} {$data['description']}
";
            } else {
                foreach ($data as $val) {
                    $out .= " * @$tag " . trim((string) $val) . "
";
                }
            }
        }
    }

    $out .= " */
";
    return $out;
}
