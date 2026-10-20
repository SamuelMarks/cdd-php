<?php

/**
 * Emits PHP docblock comments from structured arrays.
 *
 * @package Cdd\Docstrings
 */

declare(strict_types=1);

namespace Cdd\Docstrings;

/**
 * Sanitizes text before embedding inside a PHPDoc comment so a closing comment token cannot prematurely end the block.
 *
 * @param string $text Raw text to sanitize.
 * @return string Sanitized text safe for PHPDoc inclusion.
 */
function sanitize_docblock_text(string $text): string
{
    return str_replace('*/', '* /', $text);
}

/**
 * Emits a PHP docstring from an array.
 *
 * @param array<string, mixed> $doc Structured docblock definition with 'description' and 'tags'.
 * @return string The formatted PHP docblock comment.
 */
function emit(array $doc): string
{
    $nl = chr(10);
    $out = '/**' . $nl;

    if (!empty($doc['description'])) {
        $lines = explode($nl, sanitize_docblock_text((string) $doc['description']));
        foreach ($lines as $line) {
            $out .= ' * ' . $line . $nl;
        }
    }

    if (!empty($doc['tags']) && is_array($doc['tags'])) {
        foreach ($doc['tags'] as $tag => $data) {
            if ($tag === 'param') {
                foreach ($data as $param) {
                    $type = sanitize_docblock_text((string) ($param['type'] ?? 'mixed'));
                    $name = sanitize_docblock_text((string) ($param['name'] ?? ''));
                    $out .= ' * @param ' . $type . ' ' . $name . $nl;
                }
            } elseif ($tag === 'return') {
                $type = sanitize_docblock_text((string) ($data['type'] ?? 'mixed'));
                $desc = sanitize_docblock_text((string) ($data['description'] ?? ''));
                $out .= ' * @return ' . $type . ' ' . $desc . $nl;
            } else {
                foreach ($data as $val) {
                    $out .= ' * @' . $tag . ' ' . trim(sanitize_docblock_text((string) $val)) . $nl;
                }
            }
        }
    }

    $out .= ' */' . $nl;
    return $out;
}
