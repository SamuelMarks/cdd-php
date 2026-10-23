<?php

/**
 * Parses generated PHP CLI scripts to extract command operations using AST traversal.
 *
 * @package Cdd\Cli
 */

declare(strict_types=1);

namespace Cdd\Cli;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeFinder;

/**
 * Parses CLI code to extract OpenAPI operations using `nikic/php-parser` AST traversal.
 *
 * @param string $code The PHP CLI source code.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Paths map.
 */
function parse(string $code): array
{
    $paths = [];
    $parser = \Cdd\Classes\create_parser();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }
    if ($stmts === null || empty($stmts)) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var If_[] $ifs */
    $ifs = $nodeFinder->findInstanceOf($stmts, If_::class);

    foreach ($ifs as $ifNode) {
        if ($ifNode->cond instanceof Identical
            && $ifNode->cond->left instanceof Variable
            && $ifNode->cond->left->name === 'command'
            && $ifNode->cond->right instanceof String_
        ) {
            $opId = $ifNode->cond->right->value;
            if ($opId === '--help' || $opId === '-h' || $opId === 'mcp') {
                continue;
            }

            $parameters = [];
            $responses = [];
            foreach ($ifNode->stmts as $stmtNode) {
                $stmtDoc = $stmtNode->getDocComment();
                if ($stmtDoc !== null) {
                    $parsedStmtDoc = \Cdd\Docstrings\parse($stmtDoc->getText());
                    if (isset($parsedStmtDoc['tags']['oas-header']) && is_array($parsedStmtDoc['tags']['oas-header'])) {
                        foreach ($parsedStmtDoc['tags']['oas-header'] as $headerTag) {
                            $parts = explode(' ', (string) $headerTag, 3);
                            if (count($parts) === 3) {
                                $codeKey = $parts[0];
                                $headerName = $parts[1];
                                $headerObj = json_decode($parts[2], true);
                                if (!isset($responses[$codeKey]['description'])) {
                                    $responses[$codeKey]['description'] = 'Success';
                                }
                                $responses[$codeKey]['headers'][$headerName] = $headerObj;
                            }
                        }
                    }
                }
                if ($stmtNode instanceof \PhpParser\Node\Stmt\Expression && $stmtNode->expr instanceof Assign) {
                    $assign = $stmtNode->expr;
                    if ($assign->var instanceof ArrayDimFetch
                        && $assign->var->var instanceof Variable
                        && $assign->var->var->name === 'params'
                        && $assign->var->dim instanceof String_
                    ) {
                        $paramName = $assign->var->dim->value;
                        $decodedOasParam = null;
                        if ($stmtDoc !== null) {
                            $parsedParamDoc = \Cdd\Docstrings\parse($stmtDoc->getText());
                            if (isset($parsedParamDoc['tags']['oas-param'][0])) {
                                $decodedOasParam = json_decode((string) $parsedParamDoc['tags']['oas-param'][0], true);
                            }
                        }
                        if (is_array($decodedOasParam)) {
                            $parameters[] = $decodedOasParam;
                        } else {
                            $parameters[] = [
                                'name' => $paramName,
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'string'],
                            ];
                        }
                    }
                }
            }

            $opObj = [
                'operationId' => $opId,
                'description' => 'Auto-parsed from CLI command ' . $opId,
            ];
            if (!empty($parameters)) {
                $opObj['parameters'] = $parameters;
            }
            if (!empty($responses)) {
                $opObj['responses'] = $responses;
            }

            $paths['/cli/' . $opId] = [
                'post' => $opObj,
            ];
        }
    }

    return $paths;
}
