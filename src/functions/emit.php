<?php

/**
 * Emits standalone PHP functions from extracted AST nodes and tokens.
 *
 * @package Cdd\Functions
 */

declare(strict_types=1);

namespace Cdd\Functions;

use PhpParser\Node;
use PhpParser\Token;

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

    /** @var Node $node */
    $node = $functionInfo['node'];
    /** @var array<int, Token> $tokens */
    $tokens = $functionInfo['tokens'];

    return \Cdd\Classes\extract_node_source($node, $tokens);
}
