<?php

declare(strict_types=1);

namespace Cdd\Security;

/**
 * Emits PHP middleware logic representation for Security Requirement Objects.
 */
function emit(array $security): string {
    $out = "    // Security Requirements
";
    foreach ($security as $req) {
        foreach ($req as $name => $scopes) {
            $scopesStr = empty($scopes) ? '[]' : "['" . implode("', '", $scopes) . "']";
            $out .= "    \$this->requireSecurity('$name', $scopesStr);
";
        }
    }
    return $out;
}
