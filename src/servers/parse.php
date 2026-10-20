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

/**
 * Parses PHP class to extract OpenAPI Server Objects.
 *
 * @param string $code The PHP source code containing server URL properties.
 * @return array<int, array<string, string>> Extracted OpenAPI Server Objects.
 */
function parse(string $code): array
{
    $parser = \Cdd\Classes\create_parser();
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
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateServerObject(mixed $server): void
{
    if (!is_array($server)) {
        throw \Cdd\Exceptions\CddException::validation('Server must be an object');
    }
    if (!isset($server['url']) || !is_string($server['url'])) {
        throw \Cdd\Exceptions\CddException::validation('Server object must contain a "url" string');
    }
    if (isset($server['description']) && !is_string($server['description'])) {
        throw \Cdd\Exceptions\CddException::validation('Server "description" must be a string');
    }
    if (isset($server['name']) && !is_string($server['name'])) {
        throw \Cdd\Exceptions\CddException::validation('Server "name" must be a string');
    }
    if (isset($server['variables'])) {
        if (!is_array($server['variables'])) {
            throw \Cdd\Exceptions\CddException::validation('Server "variables" must be a map');
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
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateServerVariableObject(mixed $varObj): void
{
    if (!is_array($varObj)) {
        throw \Cdd\Exceptions\CddException::validation('Server variable must be an object');
    }
    if (!isset($varObj['default']) || !is_string($varObj['default'])) {
        throw \Cdd\Exceptions\CddException::validation('Server variable must contain a "default" string');
    }
    if (isset($varObj['enum'])) {
        if (!is_array($varObj['enum']) || empty($varObj['enum'])) {
            throw \Cdd\Exceptions\CddException::validation('Server variable "enum" must be a non-empty array');
        }
        foreach ($varObj['enum'] as $val) {
            if (!is_string($val)) {
                throw \Cdd\Exceptions\CddException::validation('Server variable "enum" values must be strings');
            }
        }
        if (!in_array($varObj['default'], $varObj['enum'], true)) {
            throw \Cdd\Exceptions\CddException::validation('Server variable "default" value must exist in "enum"');
        }
    }
    if (isset($varObj['description']) && !is_string($varObj['description'])) {
        throw \Cdd\Exceptions\CddException::validation('Server variable "description" must be a string');
    }
}
