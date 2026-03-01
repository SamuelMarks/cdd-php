<?php

declare(strict_types=1);

namespace Cdd\Functions;

use PhpParser\Node\Stmt\Function_;

/**
 * Emits a PHP function from its extracted node.
 */
function emit(array $functionInfo): string {
    if (!isset($functionInfo['node']) || !isset($functionInfo['tokens'])) {
        return '';
    }
    
    /** @var Function_ $node */
    $node = $functionInfo['node'];
    $tokens = $functionInfo['tokens'];
    
    $start = $node->getStartTokenPos();
    $end = $node->getEndTokenPos();
    
    $comments = $node->getAttribute('comments');
    if ($comments && count($comments) > 0) {
        $firstCommentStart = $comments[0]->getStartTokenPos();
        if ($firstCommentStart !== -1 && $firstCommentStart < $start) {
            $start = $firstCommentStart;
            // Include leading whitespace if available
            if ($start > 0 && $tokens[$start - 1]->id === T_WHITESPACE) {
                // We keep it tight for exact reproduction where applicable
            }
        }
    }
    
    $code = '';
    for ($i = $start; $i <= $end; $i++) {
        $code .= $tokens[$i]->text;
    }
    
    return ltrim($code);
}
