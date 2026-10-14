<?php

/**
 * Emits PHP class properties representing base URLs from OpenAPI Server Objects.
 *
 * @package Cdd\Servers
 */

declare(strict_types=1);

namespace Cdd\Servers;

/**
 * Emits PHP class properties representing base URLs from OpenAPI Server Objects.
 *
 * @param array<int, array<string, mixed>> $servers List of OpenAPI Server Objects.
 * @return string The emitted PHP property declarations.
 */
function emit(array $servers): string
{
    $out = '';
    foreach ($servers as $index => $server) {
        $url = (string) ($server['url'] ?? '');
        if ($url !== '') {
            $desc = (string) ($server['description'] ?? "Server $index");
            $out .= "    /** $desc */
";
            $out .= "    public string \$serverUrl{$index} = '$url';

";
        }
    }
    return $out;
}
