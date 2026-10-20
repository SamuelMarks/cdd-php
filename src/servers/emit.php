<?php

/**
 * Emits PHP class properties representing base URLs from OpenAPI Server Objects.
 *
 * @package Cdd\Servers
 */

declare(strict_types=1);

namespace Cdd\Servers;

/**
 * Emits PHP class properties representing base URLs from Server Objects.
 *
 * @param array<int, mixed> $servers List of OpenAPI Server Objects.
 * @return string The generated PHP property declarations.
 */
function emit(array $servers): string
{
    $nl = chr(10);
    $out = '';
    foreach ($servers as $index => $server) {
        if (isset($server['url'])) {
            $url = (string) $server['url'];
            if (isset($server['variables']) && is_array($server['variables'])) {
                foreach ($server['variables'] as $varName => $varInfo) {
                    $defaultVal = (string) ($varInfo['default'] ?? '');
                    $url = str_replace("{{$varName}}", $defaultVal, $url);
                }
            }
            $desc = \Cdd\Docstrings\sanitize_docblock_text((string) ($server['description'] ?? "Server $index"));
            $exportedUrl = var_export($url, true);
            $out .= "    /** {$desc} */" . $nl;
            $out .= "    public string \$serverUrl{$index} = {$exportedUrl};" . $nl . $nl;
        }
    }
    return $out;
}
