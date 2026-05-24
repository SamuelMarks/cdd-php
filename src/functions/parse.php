<?php

declare(strict_types=1);

namespace Cdd\Functions;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Stmt\Function_;

/**
 * Parses PHP source code to extract standalone functions using nikic/php-parser.
 */
function parse(string $code): array
{
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $tokens = $parser->getTokens();
    $nodeFinder = new NodeFinder();
    /** @var Function_[] $functionNodes */
    $functionNodes = $nodeFinder->findInstanceOf($stmts, Function_::class);

    $functions = [];
    foreach ($functionNodes as $node) {
        $name = '';
        if ($node->name) {
            $name = $node->name->toString();
        }
        $functions[] = [
            'name' => $name,
            'node' => $node,
            'tokens' => $tokens,
            'stmts' => $stmts,
            'code' => $code,
        ];
    }

    return $functions;
}
