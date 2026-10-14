<?php

/**
 * Parses PHP client SDK classes to extract OpenAPI operations.
 *
 * @package Cdd\Client
 */

declare(strict_types=1);

namespace Cdd\Client;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Parses client PHP code to extract operations.
 *
 * @param string $clientCode The PHP client class source code.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Paths map.
 */
function parse(string $clientCode): array
{
    $operations = [];
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($clientCode);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts ?? [], ClassMethod::class);

    $validMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'];

    foreach ($methods as $methodNode) {
        if (!$methodNode->isPublic() || $methodNode->name->toString() === '__construct') {
            continue;
        }

        $isCurl = false;
        $httpMethod = 'get';
        $path = '/';

        if ($methodNode->stmts === null) {
            continue;
        }

        foreach ($methodNode->stmts as $stmt) {
            $calls = $nodeFinder->findInstanceOf([$stmt], FuncCall::class);
            foreach ($calls as $call) {
                if ($call->name instanceof Name && $call->name->toString() === 'curl_exec') {
                    $isCurl = true;
                }
                if ($call->name instanceof Name && $call->name->toString() === 'strtoupper') {
                    if (count($call->args) > 0 && $call->args[0]->value instanceof String_) {
                        $httpMethod = strtolower($call->args[0]->value->value);
                    }
                }
            }

            if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                if ($stmt->expr->var instanceof Variable && $stmt->expr->var->name === 'url') {
                    if ($stmt->expr->expr instanceof InterpolatedString) {
                        foreach ($stmt->expr->expr->parts as $part) {
                            if ($part instanceof InterpolatedStringPart) {
                                $path = $part->value;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($isCurl) {
            $operationId = $methodNode->name->toString();
            if (!isset($operations[$path])) {
                $operations[$path] = [];
            }
            if (in_array($httpMethod, $validMethods, true)) {
                $operations[$path][$httpMethod] = [
                    'operationId' => $operationId,
                ];
            } else {
                if (!isset($operations[$path]['additionalOperations'])) {
                    $operations[$path]['additionalOperations'] = [];
                }
                $operations[$path]['additionalOperations'][strtoupper($httpMethod)] = [
                    'operationId' => $operationId,
                ];
            }
        }
    }

    return $operations;
}
