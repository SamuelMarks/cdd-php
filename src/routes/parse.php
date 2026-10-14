<?php

/**
 * Parses Laravel-style `Route::<method>(...)` statements into OpenAPI Paths Objects.
 *
 * @package Cdd\Routes
 */

declare(strict_types=1);

namespace Cdd\Routes;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;

/**
 * Parses PHP code to extract routing definitions.
 *
 * @param string $code The PHP routes file source code.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Paths map.
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
    /** @var StaticCall[] $staticCalls */
    $staticCalls = $nodeFinder->findInstanceOf($stmts ?? [], StaticCall::class);

    $validMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'];
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
                        $operationId = $method . preg_replace('/[^a-zA-Z0-9]/', '', $path);
                        if (isset($call->args[1])) {
                            $secondArg = $call->args[1]->value;
                            if ($secondArg instanceof String_ && $secondArg->value !== '' && strpos($secondArg->value, '@') === false) {
                                $operationId = $secondArg->value;
                            } elseif ($secondArg instanceof Array_ && count($secondArg->items) >= 2) {
                                $firstItem = $secondArg->items[0]->value ?? null;
                                $secondItem = $secondArg->items[1]->value ?? null;
                                if ($secondItem instanceof String_ && $secondItem->value !== '__invoke') {
                                    $operationId = $secondItem->value;
                                } elseif ($firstItem instanceof ClassConstFetch && $firstItem->class instanceof Name) {
                                    $parts = explode('\\', $firstItem->class->toString());
                                    $classShort = (string) end($parts);
                                    if (str_ends_with($classShort, 'Controller') && strlen($classShort) > 10) {
                                        $operationId = lcfirst(substr($classShort, 0, -10));
                                    }
                                }
                            }
                        }
                        $op = [
                            'operationId' => $operationId,
                            'responses' => [
                                '200' => [
                                    'description' => 'Successful operation',
                                ],
                            ],
                        ];
                        if (in_array($method, $validMethods, true)) {
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
