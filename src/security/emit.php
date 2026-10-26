<?php

/**
 * Emits PHP middleware security requirement calls from OpenAPI Security Requirement Objects.
 *
 * @package Cdd\Security
 */

declare(strict_types=1);

namespace Cdd\Security;

/**
 * Emits PHP middleware logic representation for Security Requirement Objects.
 *
 * @param array<int, array<string, array<int, string>>> $security The list of Security Requirement Objects.
 * @return string The emitted PHP security check statements.
 */
function emit(array $security): string
{
    $out = "    // Security Requirements
";
    $out .= "    \$headers ??= [];
";
    $out .= "    \$params ??= [];
";
    foreach ($security as $req) {
        foreach ($req as $name => $scopes) {
            $scopesStr = '[]';
            if (!empty($scopes)) {
                $exportedScopes = array_map(static fn (string $s): string => var_export($s, true), $scopes);
                $scopesStr = '[' . implode(', ', $exportedScopes) . ']';
            }
            $exportedName = var_export((string) $name, true);
            $out .= "    \$this->requireSecurity({$exportedName}, {$scopesStr}, \$headers, \$params);
";
        }
    }
    return $out;
}
