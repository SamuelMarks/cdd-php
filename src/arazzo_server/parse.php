<?php

/**
 * Parses PHP server workflow orchestrator (`WorkflowEngine.php`) and E2E workflow tests (`ArazzoWorkflowTest.php`) back into Arazzo 1.1.0 specifications.
 *
 * @package Cdd\ArazzoServer
 */

declare(strict_types=1);

namespace Cdd\ArazzoServer;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

/**
 * Parses PHP server workflow orchestrator or E2E test suite source code into an Arazzo 1.1.0 array.
 *
 * @param string $code The PHP source code to parse.
 * @return array<string, mixed> Reconstructed Arazzo 1.1.0 specification array.
 */
function parse(string $code): array
{
    if (preg_match('/@arazzo-spec\s+(\{[^\r\n]+\})/', $code, $m)) {
        $decoded = json_decode($m[1], true);
        if (is_array($decoded) && isset($decoded['arazzo'], $decoded['workflows'])) {
            return $decoded;
        }
    }

    if (!preg_match('/class\s+\w+Test\b/', $code)) {
        $fromClientAst = \Cdd\ArazzoClient\parse($code);
        if (!empty($fromClientAst) && !empty($fromClientAst['workflows'])) {
            return $fromClientAst;
        }
    }

    $parser = \Cdd\Classes\create_parser();
    try {
        $stmts = $parser->parse($code);
    } catch (\Throwable $e) {
        return [];
    }
    if ($stmts === null || empty($stmts)) {
        return [];
    }

    $nodeFinder = new NodeFinder();
    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts, ClassMethod::class);

    $workflows = [];
    foreach ($methods as $method) {
        $mName = $method->name->toString();
        if (!str_starts_with($mName, 'test')) {
            continue;
        }
        $wfId = lcfirst(substr($mName, 4)) ?: $mName;
        /** @var MethodCall[] $calls */
        $calls = $nodeFinder->findInstanceOf($method->stmts ?? [], MethodCall::class);
        $steps = [];
        $idx = 1;
        foreach ($calls as $call) {
            if ($call->name instanceof Identifier && $call->name->toString() === 'call' && count($call->args) >= 2) {
                $verb = $call->args[0]->value instanceof String_ ? strtolower($call->args[0]->value->value) : 'get';
                $path = $call->args[1]->value instanceof String_ ? $call->args[1]->value->value : '/';
                $steps[] = [
                    'stepId' => 'step_' . $idx++,
                    'operationPath' => '{$sourceDescriptions.default.url}#/paths/' . str_replace('/', '~1', ltrim($path, '/')) . '/' . $verb,
                    'successCriteria' => [
                        ['condition' => '$statusCode == 200'],
                    ],
                ];
            }
        }
        if (!empty($steps)) {
            $workflows[] = [
                'workflowId' => $wfId,
                'steps' => $steps,
            ];
        }
    }

    if (empty($workflows)) {
        return [];
    }

    return [
        'arazzo' => '1.1.0',
        'info' => [
            'title' => 'Extracted Server Workflows',
            'version' => '1.0.0',
        ],
        'sourceDescriptions' => [
            [
                'name' => 'default',
                'url' => './openapi.json',
                'type' => 'openapi',
            ],
        ],
        'workflows' => $workflows,
    ];
}
