<?php

/**
 * Evaluates Arazzo 1.0.x & 1.1.0 runtime expressions, JSON Pointer, JSONPath, XPath,
 * Criterion Objects, Selector Objects, and Payload Replacements.
 *
 * @package Cdd\Arazzo
 */

declare(strict_types=1);

namespace Cdd\Arazzo;

/**
 * Validates whether a string is a syntactically valid Arazzo runtime expression.
 *
 * @param string $expr The expression string to check.
 * @return bool True if it matches a valid Arazzo runtime expression root.
 */
function validateRuntimeExpression(string $expr): bool
{
    if ($expr === '$url' || $expr === '$method' || $expr === '$statusCode' || $expr === '$self') {
        return true;
    }
    $prefixes = [
        '$request.header.',
        '$request.query.',
        '$request.path.',
        '$request.body',
        '$response.header.',
        '$response.body',
        '$message.header.',
        '$message.payload',
        '$inputs.',
        '$outputs.',
        '$steps.',
        '$workflows.',
        '$sourceDescriptions.',
        '$components.',
    ];
    foreach ($prefixes as $prefix) {
        if (str_starts_with($expr, $prefix)) {
            return true;
        }
    }
    return false;
}

/**
 * Resolves an RFC 6901 JSON Pointer against an array or scalar value.
 *
 * @param mixed $data The data structure to traverse.
 * @param string $pointer The JSON Pointer (e.g., `/data/items/0` or `#/data/items/0`).
 * @return mixed The resolved value or null if not found.
 */
function getJsonPointer(mixed $data, string $pointer): mixed
{
    $clean = ltrim($pointer, '#');
    if ($clean === '' || $clean === '/') {
        return $data;
    }
    $parts = explode('/', ltrim($clean, '/'));
    $curr = $data;
    foreach ($parts as $rawToken) {
        $token = str_replace(['~1', '~0'], ['/', '~'], $rawToken);
        if (is_array($curr) && array_key_exists($token, $curr)) {
            $curr = $curr[$token];
        } else {
            return null;
        }
    }
    return $curr;
}

/**
 * Mutates a target array at an RFC 6901 JSON Pointer location.
 *
 * @param array<string|int, mixed> &$data The array to mutate in place.
 * @param string $pointer The JSON Pointer path (e.g., `/user/id`).
 * @param mixed $value The value to assign.
 * @return void
 */
function setJsonPointer(array &$data, string $pointer, mixed $value): void
{
    $clean = ltrim($pointer, '#');
    $parts = explode('/', ltrim($clean, '/'));
    $curr = &$data;
    $lastIdx = count($parts) - 1;
    foreach ($parts as $idx => $rawToken) {
        $token = str_replace(['~1', '~0'], ['/', '~'], $rawToken);
        $key = (ctype_digit($token) && array_key_exists((int) $token, $curr)) ? (int) $token : $token;
        if ($idx === $lastIdx) {
            $curr[$key] = $value;
        } else {
            if (!isset($curr[$key]) || !is_array($curr[$key])) {
                $curr[$key] = [];
            }
            $curr = &$curr[$key];
        }
    }
}

/**
 * Evaluates a JSONPath query (`$` root, `.prop`, `[index]`, `[*]`, `..prop`) against an array.
 *
 * @param mixed $data The data structure to query.
 * @param string $path The JSONPath expression (e.g., `$.items[0].id`).
 * @return mixed The matched value (or array of values for wildcards/descendants), or null if no match.
 */
function queryJsonPath(mixed $data, string $path): mixed
{
    $trimmed = trim($path);
    if (str_starts_with($trimmed, '/')) {
        return getJsonPointer($data, $trimmed);
    }
    $expr = ltrim($trimmed, '$');
    if ($expr === '') {
        return $data;
    }
    if (str_starts_with($expr, '..')) {
        $prop = ltrim(substr($expr, 2), '.');
        $results = [];
        $collect = static function (mixed $node) use (&$collect, &$results, $prop): void {
            if (is_array($node)) {
                if (array_key_exists($prop, $node)) {
                    $results[] = $node[$prop];
                }
                foreach ($node as $child) {
                    $collect($child);
                }
            }
        };
        $collect($data);
        return empty($results) ? null : $results;
    }

    $normalized = (string) preg_replace('/\[(\d+|\*|\x27[^\x27]+\x27|"[^"]+")\]/', '.$1', $expr);
    $segments = array_values(array_filter(explode('.', $normalized), static fn (string $s): bool => $s !== ''));
    $curr = $data;
    foreach ($segments as $seg) {
        $unquoted = trim($seg, "\x27\"");
        if ($unquoted === '*') {
            return is_array($curr) ? array_values($curr) : null;
        }
        if (is_array($curr) && array_key_exists($unquoted, $curr)) {
            $curr = $curr[$unquoted];
        } else {
            return null;
        }
    }
    return $curr;
}

/**
 * Replaces a value at a JSONPath location within an array.
 *
 * @param array<string|int, mixed> &$data The array to mutate in place.
 * @param string $path The JSONPath target expression (e.g., `$.user.name`).
 * @param mixed $value The replacement value.
 * @return void
 */
function replaceJsonPath(array &$data, string $path, mixed $value): void
{
    $normalized = (string) preg_replace('/\[(\d+|\x27[^\x27]+\x27|"[^"]+")\]/', '/$1', ltrim(trim($path), '$'));
    $pointer = '/' . ltrim(str_replace(['.', "'", '"'], ['/', '', ''], $normalized), '/');
    setJsonPointer($data, $pointer, $value);
}

/**
 * Evaluates an XPath expression against an XML string or associative array.
 *
 * @param mixed $xmlOrData XML string or array representation.
 * @param string $xpathExpr The XPath query string.
 * @return mixed Extracted string/boolean/number value or null when unmatched.
 */
function queryXPath(mixed $xmlOrData, string $xpathExpr): mixed
{
    if (is_array($xmlOrData)) {
        return getJsonPointer($xmlOrData, $xpathExpr);
    }
    if (!is_string($xmlOrData) || trim($xmlOrData) === '') {
        return null;
    }
    $dom = new \DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xmlOrData);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$loaded) {
        return null;
    }
    $xpath = new \DOMXPath($dom);
    $res = @$xpath->evaluate($xpathExpr);
    if ($res instanceof \DOMNodeList) {
        if ($res->length === 0) {
            return null;
        }
        $first = $res->item(0);
        return $first !== null ? $first->nodeValue : null;
    }
    return $res !== false ? $res : null;
}

/**
 * Looks up a header value case-insensitively in a headers map.
 *
 * @param array<string, mixed> $headers Map of header names to values.
 * @param string $name Target header name.
 * @return mixed Header value or null if not present.
 */
function lookupHeaderCaseInsensitive(array $headers, string $name): mixed
{
    $lower = strtolower($name);
    foreach ($headers as $k => $v) {
        if (strtolower((string) $k) === $lower) {
            return $v;
        }
    }
    return null;
}

/**
 * Traverses nested dot-separated or `#` JSON Pointer paths within an associative array.
 *
 * @param mixed $root The starting value/array.
 * @param string $remainder Remaining path after the prefix (e.g., `user.id` or `#/user/id`).
 * @return mixed Resolved value or null.
 */
function resolveSubPath(mixed $root, string $remainder): mixed
{
    if ($remainder === '') {
        return $root;
    }
    if (str_contains($remainder, '#')) {
        [$beforeHash, $pointer] = explode('#', $remainder, 2);
        $base = $beforeHash !== '' ? resolveSubPath($root, ltrim($beforeHash, '.')) : $root;
        return getJsonPointer($base, $pointer);
    }
    $clean = ltrim($remainder, '.');
    $parts = explode('.', $clean);
    $curr = $root;
    foreach ($parts as $p) {
        if (is_array($curr) && array_key_exists($p, $curr)) {
            $curr = $curr[$p];
        } else {
            return null;
        }
    }
    return $curr;
}

/**
 * Evaluates a single Arazzo runtime expression token against a runtime context.
 *
 * @param string $expr Single runtime expression (starting with `$`).
 * @param array<string, mixed> $context Execution state context (`url`, `method`, `statusCode`, `request`, `response`, `message`, `inputs`, `outputs`, `steps`, `workflows`, `sourceDescriptions`, `components`, `self`).
 * @return mixed The resolved value.
 */
function evaluateSingleRuntimeToken(string $expr, array $context): mixed
{
    if ($expr === '$url') {
        return $context['url'] ?? null;
    }
    if ($expr === '$method') {
        return $context['method'] ?? null;
    }
    if ($expr === '$statusCode') {
        return $context['statusCode'] ?? null;
    }
    if ($expr === '$self') {
        return $context['self'] ?? null;
    }
    if (str_starts_with($expr, '$request.header.')) {
        $hName = substr($expr, strlen('$request.header.'));
        return lookupHeaderCaseInsensitive((array) ($context['request']['headers'] ?? []), $hName);
    }
    if (str_starts_with($expr, '$request.query.')) {
        $qName = substr($expr, strlen('$request.query.'));
        return $context['request']['query'][$qName] ?? null;
    }
    if (str_starts_with($expr, '$request.path.')) {
        $pName = substr($expr, strlen('$request.path.'));
        return $context['request']['path'][$pName] ?? null;
    }
    if (str_starts_with($expr, '$request.body')) {
        $rem = substr($expr, strlen('$request.body'));
        return resolveSubPath($context['request']['body'] ?? null, $rem);
    }
    if (str_starts_with($expr, '$response.header.')) {
        $hName = substr($expr, strlen('$response.header.'));
        return lookupHeaderCaseInsensitive((array) ($context['response']['headers'] ?? []), $hName);
    }
    if (str_starts_with($expr, '$response.body')) {
        $rem = substr($expr, strlen('$response.body'));
        return resolveSubPath($context['response']['body'] ?? null, $rem);
    }
    if (str_starts_with($expr, '$message.header.')) {
        $hName = substr($expr, strlen('$message.header.'));
        return lookupHeaderCaseInsensitive((array) ($context['message']['headers'] ?? []), $hName);
    }
    if (str_starts_with($expr, '$message.payload')) {
        $rem = substr($expr, strlen('$message.payload'));
        return resolveSubPath($context['message']['payload'] ?? null, $rem);
    }
    if (str_starts_with($expr, '$inputs.')) {
        $rem = substr($expr, strlen('$inputs.'));
        return resolveSubPath($context['inputs'] ?? [], $rem);
    }
    if (str_starts_with($expr, '$outputs.')) {
        $rem = substr($expr, strlen('$outputs.'));
        return resolveSubPath($context['outputs'] ?? [], $rem);
    }
    if (str_starts_with($expr, '$steps.')) {
        $rem = substr($expr, strlen('$steps.'));
        return resolveSubPath($context['steps'] ?? [], $rem);
    }
    if (str_starts_with($expr, '$workflows.')) {
        $rem = substr($expr, strlen('$workflows.'));
        return resolveSubPath($context['workflows'] ?? [], $rem);
    }
    if (str_starts_with($expr, '$sourceDescriptions.')) {
        $rem = substr($expr, strlen('$sourceDescriptions.'));
        return resolveSubPath($context['sourceDescriptions'] ?? [], $rem);
    }
    if (str_starts_with($expr, '$components.')) {
        $rem = substr($expr, strlen('$components.'));
        return resolveSubPath($context['components'] ?? [], $rem);
    }
    return $expr;
}

/**
 * Evaluates a runtime expression, embedded template string (`"Bearer {$steps.auth.outputs.token}"`),
 * or recursively resolves expressions inside an array structure.
 *
 * @param mixed $expr The expression, template string, or array structure.
 * @param array<string, mixed> $context The runtime execution context.
 * @return mixed The evaluated result.
 */
function evaluateRuntimeExpression(mixed $expr, array $context): mixed
{
    if (is_array($expr)) {
        if (isset($expr['context'], $expr['selector']) && is_string($expr['context']) && is_string($expr['selector'])) {
            return evaluateSelector($expr, $context);
        }
        $out = [];
        foreach ($expr as $k => $v) {
            $out[$k] = evaluateRuntimeExpression($v, $context);
        }
        return $out;
    }
    if (!is_string($expr)) {
        return $expr;
    }
    if (str_starts_with($expr, '$') && !str_contains($expr, ' ') && !str_contains($expr, '{')) {
        return evaluateSingleRuntimeToken($expr, $context);
    }
    if (str_contains($expr, '{$')) {
        return (string) preg_replace_callback('/\{(\$[a-zA-Z0-9_\-\.\/\#~]+)\}/', static function (array $m) use ($context): string {
            $val = evaluateSingleRuntimeToken($m[1], $context);
            return is_scalar($val) ? (string) $val : (string) json_encode($val);
        }, $expr);
    }
    return $expr;
}

/**
 * Evaluates an Arazzo 1.1.0 Selector Object (`context`, `selector`, `type`).
 *
 * @param array<string, mixed> $selector The Selector Object.
 * @param array<string, mixed> $context The runtime execution context.
 * @return mixed The extracted value.
 */
function evaluateSelector(array $selector, array $context): mixed
{
    $targetData = evaluateRuntimeExpression($selector['context'] ?? '$response.body', $context);
    $query = (string) ($selector['selector'] ?? '');
    $typeSpec = $selector['type'] ?? 'jsonpath';
    $dialect = is_array($typeSpec) ? (string) ($typeSpec['type'] ?? 'jsonpath') : (string) $typeSpec;

    if ($dialect === 'jsonpointer') {
        return getJsonPointer($targetData, $query);
    }
    if ($dialect === 'xpath') {
        return queryXPath($targetData, $query);
    }
    return queryJsonPath($targetData, $query);
}

/**
 * Evaluates a single atomic comparison inside a `simple` criterion condition.
 *
 * @param string $clause Atomic comparison string (e.g., `$statusCode == 200`).
 * @param array<string, mixed> $context Runtime context.
 * @return bool True if the atomic condition holds.
 */
function evaluateSimpleAtomicCondition(string $clause, array $context): bool
{
    $trimmed = trim($clause);
    if (str_starts_with($trimmed, '!')) {
        return !evaluateSimpleAtomicCondition(substr($trimmed, 1), $context);
    }
    if (preg_match('/^(.+?)\s*(==|!=|<=|>=|<|>)\s*(.+)$/', $trimmed, $m)) {
        $lhsRaw = trim($m[1]);
        $op = $m[2];
        $rhsRaw = trim($m[3]);

        $parseOperand = static function (string $token) use ($context): mixed {
            if (str_starts_with($token, '$')) {
                return evaluateSingleRuntimeToken($token, $context);
            }
            if ((str_starts_with($token, "'") && str_ends_with($token, "'")) || (str_starts_with($token, '"') && str_ends_with($token, '"'))) {
                return substr($token, 1, -1);
            }
            if ($token === 'true') {
                return true;
            }
            if ($token === 'false') {
                return false;
            }
            if ($token === 'null') {
                return null;
            }
            if (is_numeric($token)) {
                return str_contains($token, '.') ? (float) $token : (int) $token;
            }
            return $token;
        };

        $lhs = $parseOperand($lhsRaw);
        $rhs = $parseOperand($rhsRaw);

        return match ($op) {
            '==' => $lhs == $rhs,
            '!=' => $lhs != $rhs,
            '<' => $lhs < $rhs,
            '<=' => $lhs <= $rhs,
            '>' => $lhs > $rhs,
            '>=' => $lhs >= $rhs,
        };
    }

    $val = str_starts_with($trimmed, '$') ? evaluateSingleRuntimeToken($trimmed, $context) : $trimmed;
    return (bool) $val && $val !== 'false';
}

/**
 * Evaluates an Arazzo Criterion Object (`condition`, `context`, `type`).
 *
 * @param array<string, mixed> $criterion The Criterion Object.
 * @param array<string, mixed> $context The runtime execution context.
 * @return bool True if the criterion passes.
 */
function evaluateCriterion(array $criterion, array $context): bool
{
    $condition = (string) ($criterion['condition'] ?? '');
    $typeSpec = $criterion['type'] ?? 'simple';
    $type = is_array($typeSpec) ? (string) ($typeSpec['type'] ?? 'jsonpath') : (string) $typeSpec;

    $evalContext = $context;
    if (isset($criterion['context']) && is_string($criterion['context'])) {
        $resolvedContextVal = evaluateRuntimeExpression($criterion['context'], $context);
    } else {
        $resolvedContextVal = $context['response']['body'] ?? null;
    }

    if ($type === 'regex') {
        $subject = is_scalar($resolvedContextVal) ? (string) $resolvedContextVal : (string) json_encode($resolvedContextVal);
        $delim = '/' . str_replace('/', '\/', $condition) . '/';
        return @preg_match($delim, $subject) === 1;
    }
    if ($type === 'jsonpath') {
        $matched = queryJsonPath($resolvedContextVal, $condition);
        return $matched !== null && $matched !== false && $matched !== [];
    }
    if ($type === 'jsonpointer') {
        $matched = getJsonPointer($resolvedContextVal, $condition);
        return $matched !== null && $matched !== false;
    }
    if ($type === 'xpath') {
        $matched = queryXPath($resolvedContextVal, $condition);
        return $matched !== null && $matched !== false && $matched !== '';
    }

    // Simple condition evaluation supporting || and &&
    $orGroups = explode('||', $condition);
    foreach ($orGroups as $orGroup) {
        $andClauses = explode('&&', $orGroup);
        $allAndPass = true;
        foreach ($andClauses as $clause) {
            $cleanClause = trim($clause, " \t
\r\0\x0B()");
            if (!evaluateSimpleAtomicCondition($cleanClause, $evalContext)) {
                $allAndPass = false;
                break;
            }
        }
        if ($allAndPass) {
            return true;
        }
    }
    return false;
}

/**
 * Applies a list of Arazzo Payload Replacement Objects to a payload structure.
 *
 * @param mixed $payload The base payload (array or string) after initial expression evaluation.
 * @param array<int, array<string, mixed>> $replacements List of Payload Replacement Objects.
 * @param array<string, mixed> $context Runtime execution context.
 * @return mixed The payload with all replacements applied.
 */
function applyPayloadReplacements(mixed $payload, array $replacements, array $context): mixed
{
    $evaluatedPayload = evaluateRuntimeExpression($payload, $context);
    if (!is_array($evaluatedPayload)) {
        return $evaluatedPayload;
    }
    foreach ($replacements as $rep) {
        $target = (string) ($rep['target'] ?? '');
        $val = evaluateRuntimeExpression($rep['value'] ?? null, $context);
        $selSpec = $rep['targetSelectorType'] ?? 'jsonpointer';
        $selType = is_array($selSpec) ? (string) ($selSpec['type'] ?? 'jsonpointer') : (string) $selSpec;

        if ($selType === 'jsonpath' || str_starts_with($target, '$')) {
            replaceJsonPath($evaluatedPayload, $target, $val);
        } else {
            setJsonPointer($evaluatedPayload, $target, $val);
        }
    }
    return $evaluatedPayload;
}
