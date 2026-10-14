<?php

/**
 * Emits PHP code representations for OpenAPI Encoding and Header Objects.
 *
 * @package Cdd\Encoding
 */

declare(strict_types=1);

namespace Cdd\Encoding;

/**
 * Emits PHP code representations for an Encoding Object.
 *
 * @param array<string, mixed> $encoding The Encoding object array.
 * @return string The PHP string representation.
 */
function emit(array $encoding): string
{
    $out = "[
";
    if (isset($encoding['contentType'])) {
        $out .= "    'contentType' => " . var_export((string) $encoding['contentType'], true) . ",
";
    }
    if (isset($encoding['headers']) && is_array($encoding['headers'])) {
        $out .= "    'headers' => [
";
        foreach ($encoding['headers'] as $name => $header) {
            $out .= "        " . var_export((string) $name, true) . " => " . \Cdd\Encoding\emit_header($header) . ",
";
        }
        $out .= "    ],
";
    }
    if (isset($encoding['style'])) {
        $out .= "    'style' => " . var_export((string) $encoding['style'], true) . ",
";
    }
    if (isset($encoding['explode'])) {
        $out .= "    'explode' => " . ($encoding['explode'] ? 'true' : 'false') . ",
";
    }
    if (isset($encoding['allowReserved'])) {
        $out .= "    'allowReserved' => " . ($encoding['allowReserved'] ? 'true' : 'false') . ",
";
    }
    $out .= "]";
    return $out;
}

/**
 * Emits PHP code for an individual Header object inside an Encoding.
 *
 * @param array<string, mixed> $header The Header object array.
 * @return string The PHP string representation.
 */
function emit_header(array $header): string
{
    $out = "[
";
    if (isset($header['description'])) {
        $out .= "            'description' => " . var_export((string) $header['description'], true) . ",
";
    }
    if (isset($header['schema']) && is_array($header['schema'])) {
        $out .= "            'schema' => ['type' => " . var_export((string) ($header['schema']['type'] ?? ''), true) . "],
";
    }
    $out .= "        ]";
    return $out;
}
