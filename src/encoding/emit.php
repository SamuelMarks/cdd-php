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
    $out = "[\n";
    if (isset($encoding['contentType'])) {
        $out .= "    'contentType' => '" . addslashes($encoding['contentType']) . "',\n";
    }
    if (isset($encoding['headers'])) {
        $out .= "    'headers' => [\n";
        foreach ($encoding['headers'] as $name => $header) {
            $out .= "        '" . addslashes((string)$name) . "' => " . \Cdd\Encoding\emit_header($header) . ",\n";
        }
        $out .= "    ],\n";
    }
    if (isset($encoding['style'])) {
        $out .= "    'style' => '" . addslashes($encoding['style']) . "',\n";
    }
    if (isset($encoding['explode'])) {
        $out .= "    'explode' => " . ($encoding['explode'] ? 'true' : 'false') . ",\n";
    }
    if (isset($encoding['allowReserved'])) {
        $out .= "    'allowReserved' => " . ($encoding['allowReserved'] ? 'true' : 'false') . ",\n";
    }
    $out .= "]";
    return $out;
}

/**
 * Emits PHP code for an individual Header object inside an Encoding.
 * 
 * @param array $header The Header object array.
 * @return string The PHP string representation.
 */
function emit_header(array $header): string {
    $out = "[\n";
    if (isset($header['description'])) {
        $out .= "            'description' => '" . addslashes($header['description']) . "',\n";
    }
    if (isset($header['schema'])) {
        $out .= "            'schema' => ['type' => '" . addslashes($header['schema']['type'] ?? '') . "'],\n";
    }
    $out .= "        ]";
    return $out;
}
