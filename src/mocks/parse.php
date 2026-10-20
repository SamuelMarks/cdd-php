<?php

/**
 * Safely parses PHP mock files using AST evaluation and validates OpenAPI Example Objects.
 *
 * @package Cdd\Mocks
 */

declare(strict_types=1);

namespace Cdd\Mocks;

use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;

/**
 * Parses mock code to extract Example Objects safely using nikic/php-parser.
 *
 * @param string $mockCode Code to parse.
 * @return array<string, array<string, mixed>> Extracted Example Objects.
 */
function parse(string $mockCode): array
{
    $examples = [];
    $parser = \Cdd\Classes\create_parser();
    try {
        $stmts = $parser->parse($mockCode);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var Return_|null $returnStmt */
    $returnStmt = $nodeFinder->findFirstInstanceOf($stmts ?? [], Return_::class);
    if ($returnStmt === null) {
        return [];
    }

    $res = \Cdd\Classes\evaluate_ast_node($returnStmt->expr);
    if (is_array($res)) {
        foreach ($res as $name => $data) {
            if (is_array($data) && (isset($data['dataValue']) || isset($data['serializedValue']) || isset($data['externalValue']))) {
                $examples[(string) $name] = $data;
            } else {
                $examples[(string) $name] = ['dataValue' => $data];
            }
        }
    }

    return $examples;
}

/**
 * Validates an Example Object or Reference Object.
 *
 * @param mixed $example The Example or Reference Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateExampleOrReferenceObject(mixed $example): void
{
    if (!is_array($example)) {
        throw \Cdd\Exceptions\CddException::validation('Example must be an object');
    }
    if (isset($example['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($example);
        return;
    }
    if (isset($example['summary']) && !is_string($example['summary'])) {
        throw \Cdd\Exceptions\CddException::validation('Example "summary" must be a string');
    }
    if (isset($example['description']) && !is_string($example['description'])) {
        throw \Cdd\Exceptions\CddException::validation('Example "description" must be a string');
    }
    if (isset($example['dataValue']) && isset($example['value'])) {
        throw \Cdd\Exceptions\CddException::validation('Example cannot contain both "dataValue" and "value"');
    }
    if (isset($example['serializedValue']) && (isset($example['value']) || isset($example['externalValue']))) {
        throw \Cdd\Exceptions\CddException::validation('Example cannot contain "serializedValue" with "value" or "externalValue"');
    }
    if (isset($example['externalValue']) && (isset($example['serializedValue']) || isset($example['value']))) {
        throw \Cdd\Exceptions\CddException::validation('Example cannot contain "externalValue" with "serializedValue" or "value"');
    }
    if (isset($example['externalValue']) && !is_string($example['externalValue'])) {
        throw \Cdd\Exceptions\CddException::validation('Example "externalValue" must be a string (URI)');
    }
    if (isset($example['serializedValue']) && !is_string($example['serializedValue'])) {
        throw \Cdd\Exceptions\CddException::validation('Example "serializedValue" must be a string');
    }
}
