<?php

/**
 * Parses PHP source code to extract classes, OpenAPI component types, and constant AST expressions.
 *
 * @package Cdd\Classes
 */

declare(strict_types=1);

namespace Cdd\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Returns a shared PHP-Parser instance for the newest supported PHP version.
 *
 * @return Parser The shared PHP-Parser instance.
 */
function create_parser(): Parser
{
    static $parser = null;
    if ($parser === null) {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
    }
    return $parser;
}

/**
 * Evaluates a PhpParser AST expression node into a native PHP scalar or array value without executing code.
 *
 * @param Node|null $node The AST expression node to evaluate.
 * @return mixed The evaluated scalar or array value.
 */
function evaluate_ast_node(?Node $node): mixed
{
    if ($node === null) {
        return null;
    }
    if ($node instanceof String_ || $node instanceof Int_ || $node instanceof Float_) {
        return $node->value;
    }
    if ($node instanceof Concat) {
        $left = evaluate_ast_node($node->left);
        $right = evaluate_ast_node($node->right);
        if ((is_scalar($left) || $left === null) && (is_scalar($right) || $right === null)) {
            return (string) $left . (string) $right;
        }
    }
    if ($node instanceof ClassConstFetch && $node->class instanceof Name && $node->name instanceof Identifier) {
        $constName = $node->name->toString();
        if (strtolower($constName) === 'class') {
            return $node->class->toString();
        }
        return $node->class->toString() . '::' . $constName;
    }
    if ($node instanceof UnaryMinus) {
        $inner = evaluate_ast_node($node->expr);
        if (is_int($inner) || is_float($inner)) {
            return -$inner;
        }
    }
    if ($node instanceof UnaryPlus) {
        $inner = evaluate_ast_node($node->expr);
        if (is_int($inner) || is_float($inner)) {
            return +$inner;
        }
    }
    if ($node instanceof ConstFetch) {
        $constName = strtolower($node->name->toString());
        if ($constName === 'true') {
            return true;
        }
        if ($constName === 'false') {
            return false;
        }
        return null;
    }
    if ($node instanceof Array_) {
        $result = [];
        foreach ($node->items as $item) {
            if ($item === null) {
                continue;
            }
            $val = evaluate_ast_node($item->value);
            if ($item->key !== null) {
                $key = evaluate_ast_node($item->key);
                $result[is_int($key) ? $key : (string) $key] = $val;
            } else {
                $result[] = $val;
            }
        }
        return $result;
    }
    return null;
}

/**
 * Parses PHP source code to extract classes using nikic/php-parser.
 *
 * @param string $code The PHP source code to parse.
 * @return array<int, array<string, mixed>> Extracted class descriptors.
 */
function parse(string $code): array
{
    $parser = create_parser();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $tokens = $parser->getTokens();
    $nodeFinder = new NodeFinder();
    /** @var Class_[] $classNodes */
    $classNodes = $nodeFinder->findInstanceOf($stmts ?? [], Class_::class);

    $tagToComponentType = [
        '@mediaType' => 'mediaTypes',
        '@parameter' => 'parameters',
        '@response' => 'responses',
        '@requestBody' => 'requestBodies',
        '@header' => 'headers',
        '@securityScheme' => 'securitySchemes',
        '@pathItem' => 'pathItems',
        '@callback' => 'callbacks',
        '@link' => 'links',
    ];

    $classes = [];
    foreach ($classNodes as $node) {
        $type = 'schemas';
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            $text = $docComment->getText();
            foreach ($tagToComponentType as $tag => $componentType) {
                if (strpos($text, $tag) !== false) {
                    $type = $componentType;
                    break;
                }
            }
        }

        $classes[] = [
            'name' => $node->name ? $node->name->toString() : '',
            'componentType' => $type,
            'node' => $node,
            'tokens' => $tokens,
            'stmts' => $stmts,
            'code' => $code,
        ];
    }

    return $classes;
}
