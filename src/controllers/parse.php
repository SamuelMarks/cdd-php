<?php

declare(strict_types=1);

namespace Cdd\Controllers;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Parses PHP code to extract OpenAPI metadata from Controller docblocks.
 */
function parse(string $code): array {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts, ClassMethod::class);

    $operations = [];
    foreach ($methods as $method) {
        $operationId = $method->name->toString();
        $op = [];
        
        $docComment = $method->getDocComment();
        if ($docComment) {
            $parsedDoc = \Cdd\Docstrings\parse($docComment->getText());
            
            if ($parsedDoc['description'] !== '') {
                // First sentence is summary, rest is description
                $descLines = explode("\n", $parsedDoc['description']);
                if (count($descLines) > 0) {
                    $op['summary'] = $descLines[0];
                    if (count($descLines) > 1) {
                        $op['description'] = implode("\n", array_slice($descLines, 1));
                    }
                }
            }
            
            if (isset($parsedDoc['tags']['tags'])) {
                $tagsArr = [];
                foreach ($parsedDoc['tags']['tags'] as $t) {
                    $tagsArr = array_merge($tagsArr, explode(',', $t));
                }
                $op['tags'] = array_map('trim', $tagsArr);
            }
            
            if (isset($parsedDoc['tags']['externalDocs'])) {
                $ext = explode(' ', $parsedDoc['tags']['externalDocs'][0], 2);
                $op['externalDocs'] = ['url' => $ext[0]];
                if (isset($ext[1])) {
                    $op['externalDocs']['description'] = $ext[1];
                }
            }
            
            if (isset($parsedDoc['tags']['oas-callback'])) {
                $op['callbacks'] = [];
                foreach ($parsedDoc['tags']['oas-callback'] as $cbTag) {
                    $parts = explode(' ', $cbTag, 2);
                    if (isset($parts[1])) {
                        $op['callbacks'][$parts[0]] = json_decode($parts[1], true);
                    }
                }
            }
            
            if (isset($parsedDoc['tags']['oas-link'])) {
                foreach ($parsedDoc['tags']['oas-link'] as $linkTag) {
                    $parts = explode(' ', $linkTag, 3);
                    if (count($parts) === 3) {
                        $code = $parts[0];
                        $linkName = $parts[1];
                        $linkObj = json_decode($parts[2], true);
                        if (!isset($op['responses'][$code]['links'])) {
                            $op['responses'][$code]['links'] = [];
                        }
                        $op['responses'][$code]['links'][$linkName] = $linkObj;
                    }
                }
            }
        }
        
        if (!empty($op)) {
            $operations[$operationId] = $op;
        }
    }

    return $operations;
}
