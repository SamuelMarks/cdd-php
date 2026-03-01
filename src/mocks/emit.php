<?php

declare(strict_types=1);

namespace Cdd\Mocks;

/**
 * Emits PHP code for the given OpenAPI Example Objects.
 * @param array $examples The examples to emit.
 * @param string $existingCode Unused in this simplified version.
 * @return string The emitted PHP code.
 */
function emit(array $examples, string $existingCode = ''): string {
    $out = "<?php\n\n// Auto-generated mock\n\nreturn [\n";
    foreach ($examples as $name => $example) {
        // Output the full Example Object structure
        $encoded = var_export($example, true);
        // indent
        $encoded = str_replace("\n", "\n    ", $encoded);
        $out .= "    '$name' => $encoded,\n";
    }
    $out .= "];\n";
    return $out;
}