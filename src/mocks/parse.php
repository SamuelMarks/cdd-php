<?php

declare(strict_types=1);

declare (strict_types=1);
namespace Cdd\Mocks;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
/**
 * Parses mock code to extract Example Objects.
 * @param string $mockCode Code to parse.
 * @return array Extracted Example Objects.
 */
function parse(string $mockCode): array
{
    $examples = [];
    try {
        // execute the code to get the array safely, or parse.
        // since it's just a config file, easiest is to include it if safe, but we use eval or require for now.
        // wait, we can just save to temp and require.
        $tmp = tempnam(sys_get_temp_dir(), 'mock');
        file_put_contents($tmp, $mockCode);
        $res = require $tmp;
        unlink($tmp);
        if (is_array($res)) {
            foreach ($res as $name => $data) {
                // Ensure it is an Example Object 3.2.0 format
                if (isset($data['dataValue']) || isset($data['serializedValue']) || isset($data['externalValue'])) {
                    $examples[$name] = $data;
                } else {
                    $examples[$name] = ['dataValue' => $data];
                }
            }
        }
    } catch (\Throwable $e) {
    }
    return $examples;
}
/**
 * Validates an Example Object or Reference Object.
 */
function validateExampleOrReferenceObject(mixed $example): void
{
    if (!is_array($example)) {
        throw new \RuntimeException('Example must be an object');
    }
    if (isset($example['$ref'])) {
        \Cdd\Openapi\validateReferenceObject($example);
        return;
    }
    if (isset($example['summary']) && !is_string($example['summary'])) {
        throw new \RuntimeException('Example "summary" must be a string');
    }
    if (isset($example['description']) && !is_string($example['description'])) {
        throw new \RuntimeException('Example "description" must be a string');
    }
    // Validate value, externalValue, dataValue, serializedValue mutual exclusions
    if (isset($example['dataValue']) && isset($example['value'])) {
        throw new \RuntimeException('Example cannot contain both "dataValue" and "value"');
    }
    if (isset($example['serializedValue']) && (isset($example['value']) || isset($example['externalValue']))) {
        throw new \RuntimeException('Example cannot contain "serializedValue" with "value" or "externalValue"');
    }
    if (isset($example['externalValue']) && (isset($example['serializedValue']) || isset($example['value']))) {
        throw new \RuntimeException('Example cannot contain "externalValue" with "serializedValue" or "value"');
    }
    if (isset($example['externalValue']) && !is_string($example['externalValue'])) {
        throw new \RuntimeException('Example "externalValue" must be a string (URI)');
    }
    if (isset($example['serializedValue']) && !is_string($example['serializedValue'])) {
        throw new \RuntimeException('Example "serializedValue" must be a string');
    }
}
