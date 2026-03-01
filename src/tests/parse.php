<?php

declare(strict_types=1);

namespace Cdd\Tests;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;

/**
 * Parses a test file to extract operations tested.
 */
function parse(string $testCode): array {
    $tested = [];
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($testCode);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var MethodCall[] $methodCalls */
    $methodCalls = $nodeFinder->findInstanceOf($stmts, MethodCall::class);

    foreach ($methodCalls as $call) {
        if ($call->name instanceof Identifier) {
            $name = $call->name->toString();
            if ($name === 'call' && count($call->args) >= 2) {
                if ($call->args[0]->value instanceof String_ && $call->args[1]->value instanceof String_) {
                    $method = strtolower($call->args[0]->value->value);
                    $path = $call->args[1]->value->value;
                    if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
                        if (!isset($tested[$method])) {
                            $tested[$method] = [];
                        }
                        $tested[$method][$path] = true;
                    } else {
                        if (!isset($tested['additionalOperations'])) {
                            $tested['additionalOperations'] = [];
                        }
                        $tested['additionalOperations'][strtoupper($method)][$path] = true;
                    }
                }
            } elseif ($name === 'get' && count($call->args) >= 1) {
                if ($call->args[0]->value instanceof String_) {
                    $path = $call->args[0]->value->value;
                    if (!isset($tested['get'])) {
                        $tested['get'] = [];
                    }
                    $tested['get'][$path] = true;
                }
            }
        }
    }

    return $tested;
}
