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
            $desc = str_replace('*/', '* /', (string) ($server['description'] ?? "Server $index"));
            $exportedUrl = var_export($url, true);
            $out .= "    /** $desc */
";
            $out .= "    public string \$serverUrl{$index} = {$exportedUrl};

";
        }
    }
    return $out;
}
