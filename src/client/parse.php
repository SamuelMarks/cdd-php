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
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;

/**
 * Parses client PHP code to extract operations.
 *
 * @param string $clientCode The PHP client class source code.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Paths map.
 */
function parse(string $clientCode): array
{
    $operations = [];
    $parser = \Cdd\Classes\create_parser();
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
        $security = [];

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

            $methodCalls = $nodeFinder->findInstanceOf([$stmt], MethodCall::class);
            foreach ($methodCalls as $mc) {
                if (
                    $mc->var instanceof Variable
                    && $mc->var->name === 'this'
                    && $mc->name instanceof Identifier
                    && $mc->name->toString() === 'requireSecurity'
                    && count($mc->args) >= 1
                ) {
                    $schemeName = \Cdd\Classes\evaluate_ast_node($mc->args[0]->value);
                    $scopes = isset($mc->args[1]) ? \Cdd\Classes\evaluate_ast_node($mc->args[1]->value) : [];
                    if (is_string($schemeName)) {
                        $security[] = [$schemeName => is_array($scopes) ? $scopes : []];
                    }
                }
            }

            if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                if ($stmt->expr->var instanceof Variable && $stmt->expr->var->name === 'urlPath') {
                    if ($stmt->expr->expr instanceof String_) {
                        $path = $stmt->expr->expr->value;
                    }
                } elseif ($stmt->expr->var instanceof Variable && $stmt->expr->var->name === 'url') {
                    if ($stmt->expr->expr instanceof InterpolatedString) {
                        foreach ($stmt->expr->expr->parts as $part) {
                            if ($part instanceof InterpolatedStringPart && $part->value !== '') {
                                $path = $part->value;
                                break;
                            }
                        }
                    } elseif ($stmt->expr->expr instanceof String_) {
                        $path = $stmt->expr->expr->value;
                    }
                }
            }
        }

        if ($isCurl) {
            $operationId = $methodNode->name->toString();
            $opData = [
                'operationId' => $operationId,
            ];
            if (!empty($security)) {
                $opData['security'] = $security;
            }
            if (preg_match_all('/\{([^}]+)\}/', $path, $pathMatches)) {
                $opData['parameters'] = [];
                foreach ($pathMatches[1] as $pathParamName) {
                    $opData['parameters'][] = [
                        'name' => $pathParamName,
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ];
                }
            }
            $docComment = $methodNode->getDocComment();
            if ($docComment !== null) {
                $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
                if (isset($parsedDoc['tags']['oas-param']) && is_array($parsedDoc['tags']['oas-param'])) {
                    if (!isset($opData['parameters'])) {
                        $opData['parameters'] = [];
                    }
                    foreach ($parsedDoc['tags']['oas-param'] as $rawParamJson) {
                        $decodedParam = json_decode((string) $rawParamJson, true);
                        if (is_array($decodedParam)) {
                            $opData['parameters'][] = $decodedParam;
                        }
                    }
                }
                if (isset($parsedDoc['tags']['oas-header']) && is_array($parsedDoc['tags']['oas-header'])) {
                    foreach ($parsedDoc['tags']['oas-header'] as $headerTag) {
                        $parts = explode(' ', (string) $headerTag, 3);
                        if (count($parts) === 3) {
                            $codeKey = $parts[0];
                            $headerName = $parts[1];
                            $headerObj = json_decode($parts[2], true);
                            if (!isset($opData['responses'][$codeKey]['description'])) {
                                $opData['responses'][$codeKey]['description'] = 'Success';
                            }
                            $opData['responses'][$codeKey]['headers'][$headerName] = $headerObj;
                        }
                    }
                }
            }
            if (!isset($operations[$path])) {
                $operations[$path] = [];
            }
            if (in_array($httpMethod, $validMethods, true)) {
                $operations[$path][$httpMethod] = $opData;
            } else {
                if (!isset($operations[$path]['additionalOperations'])) {
                    $operations[$path]['additionalOperations'] = [];
                }
                $operations[$path]['additionalOperations'][strtoupper($httpMethod)] = $opData;
            }
        }
    }

    return $operations;
}
