<?php

/**
 * Emits PHP mock files for OpenAPI Example Objects.
 *
 * @package Cdd\Mocks
 */

declare(strict_types=1);

namespace Cdd\Mocks;

/**
 * Emits PHP code for the given OpenAPI Example Objects.
 *
 * @param array<string, mixed> $examples The examples to emit.
 * @param string $existingCode Unused existing code parameter.
 * @return string The emitted PHP code.
 */
function emit(array $examples, string $existingCode = ''): string
{
    $nl = chr(10);
    $out = "<?php" . $nl . $nl . "// Auto-generated mock" . $nl . $nl . "return [" . $nl;
    foreach ($examples as $name => $example) {
        $encoded = var_export($example, true);
        $encoded = str_replace($nl, $nl . "    ", $encoded);
        $out .= "    '$name' => $encoded," . $nl;
    }
    $out .= "];" . $nl;
    return $out;
}

/**
 * Emits modular PHP mock files for the given OpenAPI Example Objects.
 *
 * @param array<string, mixed> $examples The examples to emit.
 * @return array<string, string> Map of filenames to emitted PHP code.
 */
function emit_modular(array $examples): array
{
    $files = [];
    foreach ($examples as $name => $example) {
        $encoded = var_export($example, true);
        $files["{$name}Mock.php"] = "<?php

// Auto-generated mock

return $encoded;
";
    }
    return $files;
}
