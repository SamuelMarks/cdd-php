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
    $nl = chr(10);
    $title = \Cdd\Docstrings\sanitize_docblock_text((string) ($info['title'] ?? 'API'));
    $version = \Cdd\Docstrings\sanitize_docblock_text((string) ($info['version'] ?? '1.0.0'));
    $summary = \Cdd\Docstrings\sanitize_docblock_text((string) ($info['summary'] ?? ''));
    $description = \Cdd\Docstrings\sanitize_docblock_text((string) ($info['description'] ?? ''));

    $out = '/**' . $nl;
    $out .= " * {$title} (v{$version})" . $nl;
    if ($summary !== '') {
        $out .= ' * @summary ' . $summary . $nl;
    }
    if ($description !== '') {
        $out .= ' *' . $nl;
        foreach (explode($nl, $description) as $line) {
            $out .= ' * ' . $line . $nl;
        }
    }
    if (isset($info['termsOfService']) && is_string($info['termsOfService']) && $info['termsOfService'] !== '') {
        $tos = \Cdd\Docstrings\sanitize_docblock_text($info['termsOfService']);
        $out .= ' * @termsOfService ' . $tos . $nl;
    }
    if (isset($info['contact']) && is_array($info['contact'])) {
        if (isset($info['contact']['name']) && is_string($info['contact']['name'])) {
            $cName = \Cdd\Docstrings\sanitize_docblock_text($info['contact']['name']);
            $out .= ' * @contact-name ' . $cName . $nl;
        }
        if (isset($info['contact']['url']) && is_string($info['contact']['url'])) {
            $cUrl = \Cdd\Docstrings\sanitize_docblock_text($info['contact']['url']);
            $out .= ' * @contact-url ' . $cUrl . $nl;
        }
        if (isset($info['contact']['email']) && is_string($info['contact']['email'])) {
            $cEmail = \Cdd\Docstrings\sanitize_docblock_text($info['contact']['email']);
            $out .= ' * @contact-email ' . $cEmail . $nl;
        }
    }
    if (isset($info['license']) && is_array($info['license'])) {
        if (isset($info['license']['name']) && is_string($info['license']['name'])) {
            $lName = \Cdd\Docstrings\sanitize_docblock_text($info['license']['name']);
            $out .= ' * @license-name ' . $lName . $nl;
        }
        if (isset($info['license']['identifier']) && is_string($info['license']['identifier'])) {
            $lId = \Cdd\Docstrings\sanitize_docblock_text($info['license']['identifier']);
            $out .= ' * @license-identifier ' . $lId . $nl;
        }
        if (isset($info['license']['url']) && is_string($info['license']['url'])) {
            $lUrl = \Cdd\Docstrings\sanitize_docblock_text($info['license']['url']);
            $out .= ' * @license-url ' . $lUrl . $nl;
        }
    }
    $out .= ' */' . $nl;
    return $out;
}
