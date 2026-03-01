<?php

declare(strict_types=1);

namespace Cdd\Classes;

use PhpParser\Node\Stmt\Class_;

/**
 * Emits a PHP class from its extracted node.
 */
function emit(array $classInfo): string {
    if (!isset($classInfo['node']) || !isset($classInfo['tokens'])) {
        return '';
    }
    
    /** @var Class_ $node */
    $node = $classInfo['node'];
    $tokens = $classInfo['tokens'];
    
    $start = $node->getStartTokenPos();
    $end = $node->getEndTokenPos();
    
    $comments = $node->getAttribute('comments');
    if ($comments && count($comments) > 0) {
        $firstCommentStart = $comments[0]->getStartTokenPos();
        if ($firstCommentStart !== -1 && $firstCommentStart < $start) {
            $start = $firstCommentStart;
            // Also include leading whitespace if possible
            if ($start > 0 && $tokens[$start - 1]->id === T_WHITESPACE) {
                // To avoid adding too many newlines, we can just start at the comment.
                // The exact whitespace before the comment might be an entire blank line.
            }
        }
    }
    
    $code = '';
    for ($i = $start; $i <= $end; $i++) {
        $code .= $tokens[$i]->text;
    }
    
    return ltrim($code);
}
