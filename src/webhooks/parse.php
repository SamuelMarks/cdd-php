<?php

/**
 * Parses PHP interfaces to extract OpenAPI Webhook definitions.
 *
 * @package Cdd\Webhooks
 */

declare(strict_types=1);

namespace Cdd\Webhooks;

use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Parses PHP interface to extract Webhooks definitions.
 *
 * @param string $code The PHP source code containing WebhookHandlers interface.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Webhooks map.
 */
function parse(string $code): array
{
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var Interface_[] $interfaces */
    $interfaces = $nodeFinder->findInstanceOf($stmts ?? [], Interface_::class);

    $webhooks = [];
    foreach ($interfaces as $iface) {
        if ($iface->name !== null && strtolower($iface->name->toString()) === 'webhookhandlers') {
            foreach ($iface->getMethods() as $method) {
                $name = $method->name->toString();
                $webhooks[$name] = [
                    'post' => [
                        'operationId' => $name,
                        'responses' => [
                            '200' => ['description' => 'Success'],
                        ],
                    ],
                ];
            }
        }
    }
    return $webhooks;
}
