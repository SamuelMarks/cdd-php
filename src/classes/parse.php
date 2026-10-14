<?php

/**
 * Parses PHP source code to extract classes and OpenAPI component types.
 *
 * @package Cdd\Classes
 */

declare(strict_types=1);

namespace Cdd\Classes;

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
