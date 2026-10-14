<?php

/**
 * Parses and validates OpenAPI Server and Server Variable Objects.
 *
 * @package Cdd\Servers
 */

declare(strict_types=1);

namespace Cdd\Servers;

use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Parses PHP class to extract OpenAPI Server Objects.
 *
 * @param string $code The PHP source code containing server URL properties.
 * @return array<int, array<string, string>> Extracted OpenAPI Server Objects.
 */
function parse(string $code): array
{
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var Property[] $properties */
    $properties = $nodeFinder->findInstanceOf($stmts ?? [], Property::class);

    $servers = [];
    foreach ($properties as $prop) {
        $name = $prop->props[0]->name->toString();
        if (str_starts_with($name, 'serverUrl')) {
            $valueNode = $prop->props[0]->default;
            if ($valueNode instanceof String_) {
                $url = $valueNode->value;
                $server = ['url' => $url];

                $docComment = $prop->getDocComment();
                if ($docComment !== null) {
                    $docText = trim(str_replace(['/**', '*/', '*'], '', $docComment->getText()));
                    if ($docText !== '') {
                        $server['description'] = $docText;
                    }
                }
                $servers[] = $server;
            }
        }
    }

    return $servers;
}

/**
 * Validates a Server Object.
 *
 * @param mixed $server The Server Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateServerObject(mixed $server): void
{
    if (!is_array($server)) {
        throw new \RuntimeException('Server must be an object');
    }
    if (!isset($server['url']) || !is_string($server['url'])) {
        throw new \RuntimeException('Server object must contain a "url" string');
    }
    if (isset($server['description']) && !is_string($server['description'])) {
        throw new \RuntimeException('Server "description" must be a string');
    }
    if (isset($server['name']) && !is_string($server['name'])) {
        throw new \RuntimeException('Server "name" must be a string');
    }
    if (isset($server['variables'])) {
        if (!is_array($server['variables'])) {
            throw new \RuntimeException('Server "variables" must be a map');
        }
        foreach ($server['variables'] as $varObj) {
            \Cdd\Servers\validateServerVariableObject($varObj);
        }
    }
}

/**
 * Validates a Server Variable Object.
 *
 * @param mixed $varObj The Server Variable Object to validate.
 * @return void
 * @throws \RuntimeException If validation fails.
 */
function validateServerVariableObject(mixed $varObj): void
{
    if (!is_array($varObj)) {
        throw new \RuntimeException('Server variable must be an object');
    }
    if (!isset($varObj['default']) || !is_string($varObj['default'])) {
        throw new \RuntimeException('Server variable must contain a "default" string');
    }
    if (isset($varObj['enum'])) {
        if (!is_array($varObj['enum']) || empty($varObj['enum'])) {
            throw new \RuntimeException('Server variable "enum" must be a non-empty array');
        }
        foreach ($varObj['enum'] as $val) {
            if (!is_string($val)) {
                throw new \RuntimeException('Server variable "enum" values must be strings');
            }
        }
        if (!in_array($varObj['default'], $varObj['enum'], true)) {
            throw new \RuntimeException('Server variable "default" value must exist in "enum"');
        }
    }
    if (isset($varObj['description']) && !is_string($varObj['description'])) {
        throw new \RuntimeException('Server variable "description" must be a string');
    }
}
