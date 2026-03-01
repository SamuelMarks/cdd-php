<?php

declare(strict_types=1);

namespace Cdd\Client;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * Parses client PHP code to extract operations.
 */
function parse(string $clientCode): array {
    $operations = [];
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($clientCode);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts, ClassMethod::class);

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
            // Check for curl_exec call
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

            // Check for $url = "{$this->baseUrl}...";
            if ($stmt instanceof \PhpParser\Node\Stmt\Expression && $stmt->expr instanceof Assign) {
                if ($stmt->expr->var instanceof Variable && $stmt->expr->var->name === 'url') {
                    if ($stmt->expr->expr instanceof InterpolatedString) {
                        foreach ($stmt->expr->expr->parts as $part) {
                            if ($part instanceof \PhpParser\Node\InterpolatedStringPart) {
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
            if (in_array($httpMethod, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace', 'query'])) {
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
