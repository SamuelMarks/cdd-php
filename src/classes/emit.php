<?php

/**
 * Emits a PHP class string from its extracted AST node and token stream.
 *
 * @package Cdd\Classes
 */

declare(strict_types=1);

namespace Cdd\Classes;

use PhpParser\Node\Stmt\Class_;

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

    /** @var Class_ $node */
    $node = $classInfo['node'];
    $tokens = $classInfo['tokens'];

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
