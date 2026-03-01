<?php

declare(strict_types=1);

namespace Cdd\Routes;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Identifier;

/**
 * Parses PHP code to extract routing definitions.
 */
function parse(string $code): array {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var StaticCall[] $staticCalls */
    $staticCalls = $nodeFinder->findInstanceOf($stmts, StaticCall::class);

    $routes = [];
    foreach ($staticCalls as $call) {
        if ($call->class instanceof Name && strtolower($call->class->toString()) === 'route') {
            if ($call->name instanceof Identifier) {
                $method = strtolower($call->name->toString());
                if (count($call->args) > 0) {
                        $firstArg = $call->args[0]->value;
                        if ($firstArg instanceof String_) {
                            $path = $firstArg->value;
                            if (!isset($routes[$path])) {
                                $routes[$path] = [];
                            }
                            $op = [
                                'operationId' => $method . preg_replace('/[^a-zA-Z0-9]/', '', $path),
                                'responses' => [
                                    '200' => [
                                        'description' => 'Successful operation'
                                    ]
                                ]
                            ];
                            if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
                                $routes[$path][$method] = $op;
                            } else {
                                $routes[$path]['additionalOperations'][strtoupper($method)] = $op;
                            }
                        }
                    }
            }
        }
    }

    return $routes;
}
