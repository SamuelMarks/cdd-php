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
    foreach ($security as $req) {
        foreach ($req as $name => $scopes) {
            $scopesStr = '[]';
            if (!empty($scopes)) {
                $scopesStr = "['" . implode("', '", $scopes) . "']";
            }
            $out .= "    \$this->requireSecurity('$name', $scopesStr, \$headers, \$params);
";
        }
    }
    return $out;
}
