<?php

declare(strict_types=1);

namespace Cdd\Servers;

/**
 * Emits PHP class properties representing base URLs from OpenAPI Server Objects.
 */
function emit(array $servers): string {
    $out = '';
    foreach ($servers as $index => $server) {
        $url = $server['url'] ?? '';
        if ($url !== '') {
            $desc = $server['description'] ?? "Server $index";
            $out .= "    /** $desc */
";
            $out .= "    public string \$serverUrl{$index} = '$url';

";
        }
    }
    return $out;
}
