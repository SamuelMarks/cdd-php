<?php

/**
 * Parses PHP controller classes and docblocks into OpenAPI Operation metadata.
 *
 * @package Cdd\Controllers
 */

declare(strict_types=1);

namespace Cdd\Controllers;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

/**
 * Parses PHP code to extract OpenAPI metadata from Controller docblocks and signatures.
 *
 * @param string $code The PHP controller source code.
 * @return array<string, array<string, mixed>> Map of operationId to extracted operation metadata.
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
    /** @var Class_[] $classes */
    $classes = $nodeFinder->findInstanceOf($stmts ?? [], Class_::class);
    $enclosingClassName = '';
    if (!empty($classes) && $classes[0]->name !== null) {
        $enclosingClassName = $classes[0]->name->toString();
    }

    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts ?? [], ClassMethod::class);

    $operations = [];
    foreach ($methods as $method) {
        $operationId = $method->name->toString();
        if ($operationId === '__invoke' && $enclosingClassName !== '' && str_ends_with($enclosingClassName, 'Controller')) {
            $baseOpName = substr($enclosingClassName, 0, -10);
            if ($baseOpName !== '') {
                $operationId = lcfirst($baseOpName);
            }
        }
        $op = [];
        $paramMetaMap = [];

        $docComment = $method->getDocComment();
        if ($docComment !== null) {
            $rawDocText = $docComment->getText();
            $parsedDoc = \Cdd\Docstrings\parse($rawDocText);

            if ($parsedDoc['description'] !== '') {
                $descLines = explode("
", $parsedDoc['description']);
                $op['summary'] = $descLines[0];
                $c = count($descLines);
                if ($c > 1) {
                    $descStr = $descLines[1];
                    for ($i = 2; $i < $c; $i++) {
                        $descStr .= "
" . $descLines[$i];
                    }
                    $op['description'] = $descStr;
                }
            }

            if (isset($parsedDoc['tags']['tags'])) {
                $tagsArr = [];
                foreach ($parsedDoc['tags']['tags'] as $t) {
                    $tagsArr = array_merge($tagsArr, explode(',', (string) $t));
                }
                $op['tags'] = array_map('trim', $tagsArr);
            }

            if (isset($parsedDoc['tags']['externalDocs'])) {
                $ext = explode(' ', (string) $parsedDoc['tags']['externalDocs'][0], 2);
                $op['externalDocs'] = ['url' => $ext[0]];
                if (isset($ext[1]) && trim($ext[1]) !== '') {
                    $op['externalDocs']['description'] = trim($ext[1]);
                }
            }

            if (isset($parsedDoc['tags']['oas-callback'])) {
                $op['callbacks'] = [];
                foreach ($parsedDoc['tags']['oas-callback'] as $cbTag) {
                    $parts = explode(' ', (string) $cbTag, 2);
                    if (isset($parts[1])) {
                        $op['callbacks'][$parts[0]] = json_decode($parts[1], true);
                    }
                }
            }

            if (isset($parsedDoc['tags']['oas-link'])) {
                foreach ($parsedDoc['tags']['oas-link'] as $linkTag) {
                    $parts = explode(' ', (string) $linkTag, 3);
                    if (count($parts) === 3) {
                        $codeKey = $parts[0];
                        $linkName = $parts[1];
                        $linkObj = json_decode($parts[2], true);
                        if (!isset($op['responses'][$codeKey]['links'])) {
                            $op['responses'][$codeKey]['links'] = [];
                        }
                        $op['responses'][$codeKey]['links'][$linkName] = $linkObj;
                    }
                }
            }

            if (isset($parsedDoc['tags']['param']) && is_array($parsedDoc['tags']['param'])) {
                foreach ($parsedDoc['tags']['param'] as $pTag) {
                    $rest = trim((string) ($pTag['name'] ?? ''));
                    if (preg_match('/^\$([a-zA-Z0-9_]+)(?:\s+\[([^\]]+)\])?(?:\s+(.*))?$/s', $rest, $pm)) {
                        $vName = $pm[1];
                        $metaStr = $pm[2] ?? '';
                        $descText = isset($pm[3]) ? trim($pm[3]) : null;
                        $inLoc = 'query';
                        $wireName = $vName;
                        if ($metaStr !== '') {
                            foreach (explode(';', $metaStr) as $kv) {
                                $kvParts = explode('=', $kv, 2);
                                if (count($kvParts) === 2) {
                                    if ($kvParts[0] === 'in') {
                                        $inLoc = $kvParts[1];
                                    } elseif ($kvParts[0] === 'name') {
                                        $wireName = $kvParts[1];
                                    }
                                }
                            }
                        }
                        $paramMetaMap[$vName] = [
                            'in' => $inLoc,
                            'name' => $wireName,
                            'description' => ($descText !== null && $descText !== '') ? $descText : null,
                        ];
                    }
                }
            }

            if (str_contains($rawDocText, '@return')) {
                $parsedReturns = \Cdd\Responses\parse($rawDocText);
                foreach ($parsedReturns as $statusCode => $respObj) {
                    $op['responses'][$statusCode] = array_replace_recursive($respObj, (array) ($op['responses'][$statusCode] ?? []));
                }
            }
        }

        // Parse method parameters and requestBody
        foreach ($method->params as $paramNode) {
            /** @var Variable $paramVar */
            $paramVar = $paramNode->var;
            $paramName = (string) $paramVar->name;
            $required = true;
            $typeName = 'string';
            $typeNode = $paramNode->type;
            if ($typeNode instanceof NullableType) {
                $required = false;
                $inner = $typeNode->type;
                if ($inner instanceof Identifier || $inner instanceof Name) {
                    $typeName = $inner->toString();
                }
            } elseif ($typeNode instanceof Identifier || $typeNode instanceof Name) {
                $typeName = $typeNode->toString();
            }
            if ($paramName === 'body') {
                $rb = \Cdd\RequestBodies\parse($typeName);
                $rb['required'] = $required;
                $op['requestBody'] = $rb;
            } else {
                if (!isset($op['parameters'])) {
                    $op['parameters'] = [];
                }
                $inLoc = $paramMetaMap[$paramName]['in'] ?? 'query';
                $wireName = $paramMetaMap[$paramName]['name'] ?? $paramName;
                $pDesc = $paramMetaMap[$paramName]['description'] ?? null;
                $op['parameters'][] = \Cdd\Parameters\parse($wireName, $typeName, $inLoc, $required, $pDesc);
            }
        }

        // Parse requireSecurity calls in method body
        if ($method->stmts !== null) {
            $methodCalls = $nodeFinder->findInstanceOf($method->stmts, MethodCall::class);
            $secReqs = [];
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
                        $secReqs[$schemeName] = is_array($scopes) ? array_map('strval', $scopes) : [];
                    }
                }
            }
            if (!empty($secReqs)) {
                $op['security'] = \Cdd\Security\parse($secReqs);
            }
        }

        // Parse return type if present
        $returnTypeNode = $method->returnType;
        if ($returnTypeNode instanceof Identifier || $returnTypeNode instanceof Name) {
            $retTypeStr = $returnTypeNode->toString();
            if ($retTypeStr !== 'void') {
                $parsedResp = \Cdd\Responses\parse('200', $retTypeStr);
                if (!isset($op['responses']['200'])) {
                    $op['responses']['200'] = $parsedResp['200'];
                } else {
                    $op['responses']['200'] = array_replace_recursive($parsedResp['200'], $op['responses']['200']);
                }
            }
        }

        if (!empty($op)) {
            $operations[$operationId] = $op;
        }
    }

    return $operations;
}
