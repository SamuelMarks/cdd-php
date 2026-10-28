<?php

/**
 * Bundles a payload file into a WebAssembly module as a custom section,
 * stripping non-essential debug/metadata custom sections beforehand.
 *
 * @package Cdd\Scripts
 */

declare(strict_types=1);

/**
 * Encodes an integer as an unsigned LEB128 binary string.
 *
 * @param int $value The non-negative integer to encode.
 * @return string The LEB128 encoded binary string.
 */
function encode_leb128_u(int $value): string
{
    $result = '';
    do {
        $byte = $value & 0x7F;
        $value >>= 7;
        if ($value !== 0) {
            $byte |= 0x80;
        }
        $result .= chr($byte);
    } while ($value !== 0);
    return $result;
}

/**
 * Decodes an unsigned LEB128 integer from a binary string at a given byte offset.
 *
 * @param string $data The binary string to read from.
 * @param int $offset The current byte offset (advanced past the decoded integer).
 * @return int The decoded non-negative integer.
 */
function decode_leb128_u(string $data, int &$offset): int
{
    $result = 0;
    $shift = 0;
    $len = strlen($data);
    while ($offset < $len) {
        $byte = ord($data[$offset++]);
        $result |= ($byte & 0x7F) << $shift;
        if (($byte & 0x80) === 0) {
            break;
        }
        $shift += 7;
    }
    return $result;
}

/**
 * Strips non-essential custom sections (such as `.debug_*`, `producers`, `name`,
 * and any previously appended `cdd-php-payload` section) from a WebAssembly binary.
 *
 * @param string $wasmData Raw WebAssembly binary bytes.
 * @param array<int, string> $keepCustomSections Custom section names to preserve.
 * @return string Stripped WebAssembly binary bytes.
 */
function strip_wasm_custom_sections(
    string $wasmData,
    array $keepCustomSections = ['target_features']
): string {
    if (strlen($wasmData) < 8 || substr($wasmData, 0, 4) !== "\x00asm") {
        return $wasmData;
    }

    $out = substr($wasmData, 0, 8);
    $offset = 8;
    $len = strlen($wasmData);

    while ($offset < $len) {
        $sectionStart = $offset;
        $sectionId = ord($wasmData[$offset++]);
        $sectionSize = decode_leb128_u($wasmData, $offset);
        $sectionEnd = $offset + $sectionSize;
        if ($sectionEnd > $len) {
            $out .= substr($wasmData, $sectionStart);
            break;
        }

        if ($sectionId !== 0) {
            $out .= substr($wasmData, $sectionStart, $sectionEnd - $sectionStart);
        } else {
            $nameOffset = $offset;
            $nameLen = decode_leb128_u($wasmData, $nameOffset);
            $customName = substr($wasmData, $nameOffset, $nameLen);
            if (in_array($customName, $keepCustomSections, true)) {
                $out .= substr($wasmData, $sectionStart, $sectionEnd - $sectionStart);
            }
        }

        $offset = $sectionEnd;
    }

    return $out;
}

/**
 * Builds a WebAssembly custom section (ID 0) for a given section name and binary payload.
 *
 * @param string $name Custom section name.
 * @param string $payload Raw payload bytes.
 * @return string Complete WebAssembly custom section bytes.
 */
function build_wasm_custom_section(string $name, string $payload): string
{
    $nameLenLeb = encode_leb128_u(strlen($name));
    $sectionContent = $nameLenLeb . $name . $payload;
    $sectionSizeLeb = encode_leb128_u(strlen($sectionContent));
    return chr(0) . $sectionSizeLeb . $sectionContent;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if ($argc < 3) {
        echo "Usage: php bundle_wasm_payload.php <wasm_file> <payload_file>\n";
        exit(1);
    }

    $wasmFile = $argv[1];
    $payloadFile = $argv[2];

    if (!file_exists($wasmFile) || !file_exists($payloadFile)) {
        echo "Files not found.\n";
        exit(1);
    }

    $wasmData = (string) file_get_contents($wasmFile);
    $payloadData = (string) file_get_contents($payloadFile);

    $strippedWasm = strip_wasm_custom_sections($wasmData);
    $section = build_wasm_custom_section('cdd-php-payload', $payloadData);

    file_put_contents($wasmFile, $strippedWasm . $section);

    echo "Custom section appended successfully.\n";
}
