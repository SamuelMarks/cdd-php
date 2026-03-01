<?php

declare(strict_types=1);

namespace Cdd\Webhooks;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Node\Stmt\Interface_;

/**
 * Parses PHP interface to extract Webhooks definitions.
 *
 * @param string $code
 * @return array
 */
function parse(string $code): array {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }
    
    $nodeFinder = new NodeFinder();
    /** @var Interface_[] $interfaces */
    $interfaces = $nodeFinder->findInstanceOf($stmts, Interface_::class);
    
    $webhooks = [];
    foreach ($interfaces as $iface) {
        if (strtolower($iface->name->toString()) === 'webhookhandlers') {
            foreach ($iface->getMethods() as $method) {
                $name = $method->name->toString();
                // Simple assumption: name is the webhook name
                $webhooks[$name] = [
                    'post' => [
                        'operationId' => $name,
                        'responses' => [
                            '200' => ['description' => 'Success']
                        ]
                    ]
                ];
            }
        }
    }
    return $webhooks;
}
