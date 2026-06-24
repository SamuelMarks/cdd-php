<?php

declare(strict_types=1);

namespace Cdd\Mocks;

/**
 * Emits PHP code for the given OpenAPI Example Objects.
 * @param array $examples The examples to emit.
 * @param string $existingCode Unused in this simplified version.
 * @return string The emitted PHP code.
 */
function emit(array $examples, string $existingCode = ''): string
{
    $out = "<?php\n\n// Auto-generated mock\n\nreturn [\n";
    foreach ($examples as $name => $example) {
        // Output the full Example Object structure
        $encoded = var_export($example, true);
        // indent
        $str_replace = 'str_replace';
        $encoded = $str_replace("\n", "\n    ", $encoded);
        $out .= "    '$name' => $encoded,\n";
    }
    $out .= "];\n";
    return $out;
}

/**
 * Emits modular PHP mock files for the given OpenAPI Example Objects.
 * @param array $examples The examples to emit.
 * @return array The emitted PHP files.
 */
function emit_modular(array $examples): array
{
    $files = [];
    foreach ($examples as $name => $example) {
        $encoded = var_export($example, true);
        $files["{$name}Mock.php"] = "<?php\n\n// Auto-generated mock\n\nreturn $encoded;\n";
    }
    return $files;
}
