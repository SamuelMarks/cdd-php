<?php

/**
 * Emits PHP docblock comments from OpenAPI Info Objects.
 *
 * @package Cdd\Info
 */

declare(strict_types=1);

namespace Cdd\Info;

/**
 * Emits PHP comment block from an OpenAPI Info Object.
 *
 * @param array<string, mixed> $info The OpenAPI Info Object.
 * @return string The formatted PHP docblock.
 */
function emit(array $info): string
{
    $title = (string) ($info['title'] ?? 'API');
    $version = (string) ($info['version'] ?? '1.0.0');
    $summary = (string) ($info['summary'] ?? '');
    $description = (string) ($info['description'] ?? '');

    $out = "/**
";
    $out .= " * $title (v$version)
";
    if ($summary !== '') {
        $out .= " * @summary $summary
";
    }
    if ($description !== '') {
        $out .= " *
";
        foreach (explode("
", $description) as $line) {
            $out .= " * $line
";
        }
    }
    if (isset($info['termsOfService']) && is_string($info['termsOfService']) && $info['termsOfService'] !== '') {
        $out .= " * @termsOfService {$info['termsOfService']}
";
    }
    if (isset($info['contact']) && is_array($info['contact'])) {
        if (isset($info['contact']['name']) && is_string($info['contact']['name'])) {
            $out .= " * @contact-name {$info['contact']['name']}
";
        }
        if (isset($info['contact']['url']) && is_string($info['contact']['url'])) {
            $out .= " * @contact-url {$info['contact']['url']}
";
        }
        if (isset($info['contact']['email']) && is_string($info['contact']['email'])) {
            $out .= " * @contact-email {$info['contact']['email']}
";
        }
    }
    if (isset($info['license']) && is_array($info['license'])) {
        if (isset($info['license']['name']) && is_string($info['license']['name'])) {
            $out .= " * @license-name {$info['license']['name']}
";
        }
        if (isset($info['license']['identifier']) && is_string($info['license']['identifier'])) {
            $out .= " * @license-identifier {$info['license']['identifier']}
";
        }
        if (isset($info['license']['url']) && is_string($info['license']['url'])) {
            $out .= " * @license-url {$info['license']['url']}
";
        }
    }
    $out .= " */
";
    return $out;
}
