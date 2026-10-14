<?php

/**
 * Resolves local `$ref` references and rewrites reference prefixes in OpenAPI structures.
 *
 * @package Cdd\Openapi
 */

declare(strict_types=1);

namespace Cdd\Openapi;

/**
 * Resolves all $ref references in an OpenAPI structure using the provided components.
 * Includes cycle detection to prevent infinite recursion on circular schemas.
 *
 * @param array<string|int, mixed> $structure The structure to resolve references in.
 * @param array<string, mixed> $components The components object containing the schemas.
 * @param array<string, bool> $seenRefs Set of reference URIs currently in the resolution stack.
 * @return array<string|int, mixed> The resolved structure.
 */
function resolve_refs(array $structure, array $components, array $seenRefs = []): array
{
    $resolved = [];
    foreach ($structure as $key => $value) {
        if ($key === '$ref' && is_string($value)) {
            if (strpos($value, '#/components/') === 0 && !isset($seenRefs[$value])) {
                $parts = explode('/', substr($value, 13));
                $refData = $components;
                $found = true;
                foreach ($parts as $part) {
                    if (is_array($refData) && isset($refData[$part])) {
                        $refData = $refData[$part];
                    } else {
                        $found = false;
                        break;
                    }
                }

                if ($found && is_array($refData)) {
                    $nextSeen = $seenRefs;
                    $nextSeen[$value] = true;
                    $refData = resolve_refs($refData, $components, $nextSeen);
                    foreach ($refData as $k => $v) {
                        $resolved[$k] = $v;
                    }
                    continue;
                }
            }
        }

        if (is_array($value)) {
            $resolved[$key] = resolve_refs($value, $components, $seenRefs);
        } else {
            $resolved[$key] = $value;
        }
    }
    return $resolved;
}

/**
 * Recursively rewrites `$ref` prefixes across an OpenAPI or Swagger array structure.
 *
 * @param array<string|int, mixed> $structure The structure to transform.
 * @param array<string, string> $prefixMap Map of source prefix to target prefix.
 * @return array<string|int, mixed> The transformed structure.
 */
function rewrite_ref_prefixes(array $structure, array $prefixMap): array
{
    $out = [];
    foreach ($structure as $k => $v) {
        if ($k === '$ref' && is_string($v)) {
            foreach ($prefixMap as $from => $to) {
                if (str_starts_with($v, $from)) {
                    $v = $to . substr($v, strlen($from));
                    break;
                }
            }
            $out[$k] = $v;
        } elseif (is_array($v)) {
            $out[$k] = rewrite_ref_prefixes($v, $prefixMap);
        } else {
            $out[$k] = $v;
        }
    }
    return $out;
}
