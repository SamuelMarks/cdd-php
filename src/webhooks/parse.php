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

/**
 * Parses PHP interface to extract Webhooks definitions.
 *
 * @param string $code The PHP source code containing WebhookHandlers interface.
 * @return array<string, array<string, mixed>> Extracted OpenAPI Webhooks map.
 */
function parse(string $code): array
{
    $parser = \Cdd\Classes\create_parser();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var Interface_[] $interfaces */
    $interfaces = $nodeFinder->findInstanceOf($stmts ?? [], Interface_::class);

    $validMethods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'];
    $webhooks = [];
    foreach ($interfaces as $iface) {
        if ($iface->name !== null && strtolower($iface->name->toString()) === 'webhookhandlers') {
            foreach ($iface->getMethods() as $method) {
                $name = $method->name->toString();
                $httpMethod = 'post';
                $webhookKey = $name;
                if (preg_match('/^handle_(.+)_([a-z]+)$/i', $name, $matches)) {
                    $candidateMethod = strtolower($matches[2]);
                    if (in_array($candidateMethod, $validMethods, true)) {
                        $webhookKey = $matches[1];
                        $httpMethod = $candidateMethod;
                    }
                }
                if (!isset($webhooks[$webhookKey])) {
                    $webhooks[$webhookKey] = [];
                }
                $webhooks[$webhookKey][$httpMethod] = [
                    'operationId' => $name,
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ];
            }
        }
    }
    return $webhooks;
}
