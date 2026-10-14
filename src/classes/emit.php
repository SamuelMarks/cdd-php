<?php

/**
 * Emits a PHP class string from its extracted AST node and token stream.
 *
 * @package Cdd\Classes
 */

declare(strict_types=1);

namespace Cdd\Classes;

use PhpParser\Node;
use PhpParser\Token;

/**
 * Extracts raw PHP source code for an AST node (including leading comments) from its token stream.
 *
 * @param Node $node The AST node to slice.
 * @param array<int, Token> $tokens The parser token stream.
 * @return string The extracted and trimmed PHP source code.
 */
function extract_node_source(Node $node, array $tokens): string
{
    $start = $node->getStartTokenPos();
    $end = $node->getEndTokenPos();

    $comments = $node->getAttribute('comments');
    if (is_array($comments) && count($comments) > 0) {
        $firstCommentStart = $comments[0]->getStartTokenPos();
        if ($firstCommentStart !== -1 && $firstCommentStart < $start) {
            $start = $firstCommentStart;
        }
    }

    $code = '';
    for ($i = $start; $i <= $end; $i++) {
        $code .= $tokens[$i]->text;
    }

    return ltrim($code);
}

/**
 * Emits a PHP class from its extracted node.
 *
 * @param array<string, mixed> $classInfo Extracted class metadata containing 'node' and 'tokens'.
 * @return string The emitted PHP class source code.
 */
function emit(array $classInfo): string
{
    if (!isset($classInfo['node']) || !isset($classInfo['tokens'])) {
        return '';
    }

    /** @var Node $node */
    $node = $classInfo['node'];
    /** @var array<int, Token> $tokens */
    $tokens = $classInfo['tokens'];

    return extract_node_source($node, $tokens);
}
