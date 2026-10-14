<?php

/**
 * Parses PHP source code to extract standalone functions.
 *
 * @package Cdd\Functions
 */

declare(strict_types=1);

namespace Cdd\Functions;

use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;

/**
 * Parses PHP source code to extract standalone functions using nikic/php-parser.
 *
 * @param string $code The PHP source code to parse.
 * @return array<int, array<string, mixed>> List of extracted function descriptors.
 */
function parse(string $code): array
{
    $parser = \Cdd\Classes\create_parser();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $tokens = $parser->getTokens();
    $nodeFinder = new NodeFinder();
    /** @var Function_[] $functionNodes */
    $functionNodes = $nodeFinder->findInstanceOf($stmts ?? [], Function_::class);

    $functions = [];
    foreach ($functionNodes as $node) {
        $functions[] = [
            'name' => $node->name->toString(),
            'node' => $node,
            'tokens' => $tokens,
            'stmts' => $stmts,
            'code' => $code,
        ];
    }

    return $functions;
}
