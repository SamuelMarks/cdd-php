<?php

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Resolves all $ref references in an OpenAPI structure using the provided components.
 * This is a recursive function.
 *
 * @param array $structure The structure to resolve references in.
 * @param array $components The components object containing the schemas.
 * @return array The resolved structure.
 */
function resolve_refs(array $structure, array $components): array {
    $resolved = [];
    foreach ($structure as $key => $value) {
        if ($key === '$ref' && is_string($value)) {
            // Only handle local references for now e.g., #/components/schemas/User
            if (strpos($value, '#/components/') === 0) {
                $parts = explode('/', substr($value, 13)); // remove #/components/
                $refData = $components;
                $found = true;
                foreach ($parts as $part) {
                    if (isset($refData[$part])) {
                        $refData = $refData[$part];
                    } else {
                        $found = false;
                        break;
                    }
                }
                
                if ($found) {
                    // recursively resolve the referenced data itself
                    $refData = resolve_refs($refData, $components);
                    // Merge ref data into current array, replacing $ref
                    foreach ($refData as $k => $v) {
                        $resolved[$k] = $v;
                    }
                    continue; // Skip adding the $ref key itself
                }
            }
        }
        
        if (is_array($value)) {
            $resolved[$key] = resolve_refs($value, $components);
        } else {
            $resolved[$key] = $value;
        }
    }
    return $resolved;
}
