<?php

/**
 * Emits standalone PHP functions from extracted AST nodes and tokens.
 *
 * @package Cdd\Functions
 */

declare(strict_types=1);

namespace Cdd\Functions;

use PhpParser\Node\Stmt\Function_;

/**
 * Emits a PHP function from its extracted node.
 *
 * @param array<string, mixed> $functionInfo Extracted function metadata containing 'node' and 'tokens'.
 * @return string The emitted PHP function source code.
 */
function emit(array $functionInfo): string
{
    if (!isset($functionInfo['node']) || !isset($functionInfo['tokens'])) {
        return '';
    }

    /** @var Function_ $node */
    $node = $functionInfo['node'];
    $tokens = $functionInfo['tokens'];

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
