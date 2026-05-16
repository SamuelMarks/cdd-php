<?php

/**
 * Bundles a payload file into a WebAssembly module as a custom section.
 */

if ($argc < 3) {
    echo "Usage: php bundle_wasm_payload.php <wasm_file> <payload_file>\n";
    exit(1);
}

$wasm_file = $argv[1];
$payload_file = $argv[2];

if (!file_exists($wasm_file) || !file_exists($payload_file)) {
    echo "Files not found.\n";
    exit(1);
}

$wasm_data = file_get_contents($wasm_file);
$payload_data = file_get_contents($payload_file);

// Name of the custom section
$name = "cdd-php-payload";
$name_len = strlen($name);

/**
 * Encodes an integer as an unsigned LEB128 string.
 *
 * @param int $value The integer to encode.
 * @return string The LEB128 encoded binary string.
 */
function encode_leb128_u($value)
{
    $result = '';
    do {
        $byte = $value & 0x7F;
        $value >>= 7;
        if ($value != 0) {
            $byte |= 0x80;
        }
        $result .= chr($byte);
    } while ($value != 0);
    return $result;
}

$name_len_leb = encode_leb128_u($name_len);
$payload_size = strlen($payload_data);

// Section content: name_len (LEB128) + name + payload
$section_content = $name_len_leb . $name . $payload_data;
$section_size = strlen($section_content);
$section_size_leb = encode_leb128_u($section_size);

// Section: id (0 for custom) + section_size (LEB128) + section_content
$section = chr(0) . $section_size_leb . $section_content;

file_put_contents($wasm_file, $section, FILE_APPEND);

echo "Custom section appended successfully.\n";
