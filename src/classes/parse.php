<?php

declare(strict_types=1);

namespace Cdd\Classes;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Stmt\Class_;

/**
 * Parses PHP source code to extract classes using nikic/php-parser.
 */
function parse(string $code): array {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $tokens = $parser->getTokens();
    $nodeFinder = new NodeFinder();
    /** @var Class_[] $classNodes */
    $classNodes = $nodeFinder->findInstanceOf($stmts, Class_::class);

        $classes = [];
    foreach ($classNodes as $node) {
        $type = 'schemas'; // default component type
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            $text = $docComment->getText();
            if (strpos($text, '@mediaType') !== false) $type = 'mediaTypes';
            elseif (strpos($text, '@parameter') !== false) $type = 'parameters';
            elseif (strpos($text, '@response') !== false) $type = 'responses';
            elseif (strpos($text, '@requestBody') !== false) $type = 'requestBodies';
            elseif (strpos($text, '@header') !== false) $type = 'headers';
            elseif (strpos($text, '@securityScheme') !== false) $type = 'securitySchemes';
            elseif (strpos($text, '@pathItem') !== false) $type = 'pathItems';
            elseif (strpos($text, '@callback') !== false) $type = 'callbacks';
            elseif (strpos($text, '@link') !== false) $type = 'links';
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
