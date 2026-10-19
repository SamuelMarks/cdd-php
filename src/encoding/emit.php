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
 * @param array<string, mixed> $encoding The Encoding Object.
 * @return string The emitted PHP array code.
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
            $exportedName = var_export((string) $name, true);
            $out .= "        $exportedName => " . emit_header(is_array($header) ? $header : []) . ",
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
        $val = $encoding['explode'] ? 'true' : 'false';
        $out .= "    'explode' => $val,
";
    }
    if (isset($encoding['allowReserved'])) {
        $val = $encoding['allowReserved'] ? 'true' : 'false';
        $out .= "    'allowReserved' => $val,
";
    }
    $out .= "]";
    return $out;
}

/**
 * Emits PHP code representations for a Header Object.
 *
 * @param array<string, mixed> $header The Header Object.
 * @return string The emitted PHP array code.
 */
function emit_header(array $header): string
{
    $out = "[
";
    if (isset($header['description'])) {
        $out .= "            'description' => " . var_export((string) $header['description'], true) . ",
";
    }
    if (isset($header['required'])) {
        $out .= "            'required' => " . ($header['required'] ? 'true' : 'false') . ",
";
    }
    if (isset($header['deprecated'])) {
        $out .= "            'deprecated' => " . ($header['deprecated'] ? 'true' : 'false') . ",
";
    }
    if (isset($header['style'])) {
        $out .= "            'style' => " . var_export((string) $header['style'], true) . ",
";
    }
    if (isset($header['explode'])) {
        $out .= "            'explode' => " . ($header['explode'] ? 'true' : 'false') . ",
";
    }
    if (isset($header['schema']) && is_array($header['schema'])) {
        if (count($header['schema']) === 1 && isset($header['schema']['type'])) {
            $out .= "            'schema' => ['type' => " . var_export((string) $header['schema']['type'], true) . "],
";
        } else {
            $out .= "            'schema' => " . var_export($header['schema'], true) . ",
";
        }
    }
    if (isset($header['example'])) {
        $out .= "            'example' => " . var_export($header['example'], true) . ",
";
    }
    if (isset($header['examples']) && is_array($header['examples'])) {
        $out .= "            'examples' => " . var_export($header['examples'], true) . ",
";
    }
    if (isset($header['content']) && is_array($header['content'])) {
        $out .= "            'content' => " . var_export($header['content'], true) . ",
";
    }
    $out .= "        ]";
    return $out;
}
