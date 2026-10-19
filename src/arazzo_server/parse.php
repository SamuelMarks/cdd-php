<?php

/**
 * Parses PHP server workflow orchestrator (`WorkflowEngine.php`) and E2E workflow tests (`ArazzoWorkflowTest.php`) back into Arazzo 1.1.0 specifications.
 *
 * @package Cdd\ArazzoServer
 */

declare(strict_types=1);

namespace Cdd\ArazzoServer;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;

/**
 * Parses PHP server workflow orchestrator or E2E test suite source code into an Arazzo 1.1.0 array.
 *
 * @param string $code The PHP source code to parse.
 * @return array<string, mixed> Reconstructed Arazzo 1.1.0 specification array.
 */
function parse(string $code): array
{
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
    /** @var ClassConst[] $classConsts */
    $classConsts = $nodeFinder->findInstanceOf($stmts, ClassConst::class);
    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts, ClassMethod::class);

    $constants = [];
    foreach ($classConsts as $constStmt) {
        foreach ($constStmt->consts as $c) {
            $constants[$c->name->toString()] = \Cdd\ArazzoClient\astExprToPhpValue($c->value);
        }
    }

    $workflows = [];
    foreach ($methods as $method) {
        $mName = $method->name->toString();
        if (!str_starts_with($mName, 'test')) {
            continue;
        }

        // 1. Check for AST `$workflow = [...];` assignment inside the test method
        $extractedFromVar = null;
        foreach ($method->stmts ?? [] as $stmt) {
            if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                $assign = $stmt->expr;
                if ($assign->var instanceof Variable && $assign->var->name === 'workflow' && $assign->expr instanceof Array_) {
                    $val = \Cdd\ArazzoClient\astExprToPhpValue($assign->expr);
                    if (is_array($val) && isset($val['workflowId'])) {
                        $extractedFromVar = $val;
                        break;
                    }
                }
            }
        }
        if ($extractedFromVar !== null) {
            $workflows[] = $extractedFromVar;
            continue;
        }

        // 2. Fallback: extract `$this->call(verb, path)` invocations from handwritten route flow tests
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

    $arazzo = [
        'arazzo' => is_string($constants['ARAZZO_VERSION'] ?? null) ? $constants['ARAZZO_VERSION'] : '1.1.0',
    ];

    if (isset($constants['SELF_URI']) && is_string($constants['SELF_URI']) && $constants['SELF_URI'] !== '') {
        $arazzo['$self'] = $constants['SELF_URI'];
    }
    if (isset($constants['EXTENSIONS']) && is_array($constants['EXTENSIONS'])) {
        foreach ($constants['EXTENSIONS'] as $extK => $extV) {
            $arazzo[(string) $extK] = $extV;
        }
    }

    $arazzo['info'] = is_array($constants['INFO'] ?? null) ? $constants['INFO'] : [
        'title' => 'Extracted Server Workflows',
        'version' => '1.0.0',
    ];
    $arazzo['sourceDescriptions'] = is_array($constants['SOURCE_DESCRIPTIONS'] ?? null) && !empty($constants['SOURCE_DESCRIPTIONS'])
        ? $constants['SOURCE_DESCRIPTIONS']
        : [
            [
                'name' => 'default',
                'url' => './openapi.json',
                'type' => 'openapi',
            ],
        ];
    $arazzo['workflows'] = $workflows;

    if (isset($constants['COMPONENTS']) && is_array($constants['COMPONENTS']) && !empty($constants['COMPONENTS'])) {
        $arazzo['components'] = $constants['COMPONENTS'];
    }

    return $arazzo;
}
