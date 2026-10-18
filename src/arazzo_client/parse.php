<?php

/**
 * Parses PHP client SDK workflow classes (`ApiWorkflows.php`) or chained client methods into Arazzo 1.1.0 specifications.
 *
 * @package Cdd\ArazzoClient
 */

declare(strict_types=1);

namespace Cdd\ArazzoClient;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;

/**
 * Converts a `PhpParser` AST expression node (Array_, String_, Int_, Float_, ConstFetch) into a native PHP value.
 *
 * @param Expr $expr The AST expression node.
 * @return mixed The converted PHP scalar or array value.
 */
function astExprToPhpValue(Expr $expr): mixed
{
    if ($expr instanceof String_) {
        return $expr->value;
    }
    if ($expr instanceof Int_ || $expr instanceof Float_) {
        return $expr->value;
    }
    if ($expr instanceof Expr\ConstFetch) {
        $lower = strtolower($expr->name->toString());
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        return null;
    }
    if ($expr instanceof Array_) {
        $arr = [];
        foreach ($expr->items as $item) {
            if ($item === null) {
                continue;
            }
            $val = astExprToPhpValue($item->value);
            if ($item->key !== null) {
                $k = astExprToPhpValue($item->key);
                $arr[is_int($k) ? $k : (string) $k] = $val;
            } else {
                $arr[] = $val;
            }
        }
        return $arr;
    }
    return null;
}

/**
 * Parses PHP client workflow source code (`ApiWorkflows.php` or chained SDK classes) into an Arazzo 1.1.0 specification array.
 *
 * @param string $code The PHP source code to analyze.
 * @return array<string, mixed> Reconstructed Arazzo 1.1.0 specification array.
 */
function parse(string $code): array
{
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

    // 1. Check if getSpecification() AST return array is present for exact lossless AST recovery
    foreach ($methods as $method) {
        if ($method->name->toString() === 'getSpecification' && $method->stmts !== null) {
            foreach ($method->stmts as $stmt) {
                if ($stmt instanceof Return_ && $stmt->expr instanceof Array_) {
                    $spec = astExprToPhpValue($stmt->expr);
                    if (is_array($spec) && isset($spec['arazzo'], $spec['workflows'])) {
                        return $spec;
                    }
                }
            }
        }
    }

    // 2. Reconstruct from file/class/method PHPDoc annotations and AST method bodies
    $arazzo = [
        'arazzo' => '1.1.0',
        'info' => [
            'title' => 'Extracted Workflows',
            'version' => '1.0.0',
        ],
        'sourceDescriptions' => [
            [
                'name' => 'default',
                'url' => './openapi.json',
                'type' => 'openapi',
            ],
        ],
        'workflows' => [],
    ];

    if (preg_match('/@arazzo-version\s+(\S+)/', $code, $m)) {
        $arazzo['arazzo'] = $m[1];
    }
    if (preg_match('/@arazzo-self\s+(\S+)/', $code, $m)) {
        $arazzo['$self'] = $m[1];
    }
    if (preg_match('/@title\s+([^\r
]+)/', $code, $m)) {
        $arazzo['info']['title'] = stripslashes(trim($m[1]));
    }
    if (preg_match('/@version\s+([^\r
]+)/', $code, $m)) {
        $arazzo['info']['version'] = stripslashes(trim($m[1]));
    }
    if (preg_match('/@summary\s+([^\r
]+)/', $code, $m)) {
        $arazzo['info']['summary'] = trim($m[1]);
    }
    if (preg_match('/@description\s+([^\r
]+)/', $code, $m)) {
        $arazzo['info']['description'] = trim($m[1]);
    }
    if (preg_match_all('/@arazzo-source\s+(\{[^\r
]+\})/', $code, $srcMatches)) {
        $parsedSources = [];
        foreach ($srcMatches[1] as $rawSrc) {
            $decoded = json_decode($rawSrc, true);
            if (is_array($decoded)) {
                $parsedSources[] = $decoded;
            }
        }
        if (!empty($parsedSources)) {
            $arazzo['sourceDescriptions'] = $parsedSources;
        }
    }
    if (preg_match('/@arazzo-components\s+(\{[^\r
]+\})/', $code, $compMatch)) {
        $decodedComp = json_decode($compMatch[1], true);
        if (is_array($decodedComp)) {
            $arazzo['components'] = $decodedComp;
        }
    }

    $skipMethods = ['__construct', 'getSpecification', 'executeWorkflow', 'matchesActionCriteria', 'dispatchStepOperation', 'setApiKey', 'setBearerToken', 'requireSecurity', 'mcp', 'connect_mcp', 'connect_mcp_sse', 'workflows'];

    foreach ($methods as $method) {
        $mName = $method->name->toString();
        if (!$method->isPublic() || in_array($mName, $skipMethods, true)) {
            continue;
        }
        $doc = $method->getDocComment()?->getText() ?? '';
        if (preg_match('/@arazzo-workflow\s+(\{[^\r
]+\})/', $doc, $wfMatch)) {
            $decodedWf = json_decode($wfMatch[1], true);
            if (is_array($decodedWf)) {
                $arazzo['workflows'][] = $decodedWf;
                continue;
            }
        }

        // Reverse-engineer arbitrary PHP workflow method calls into Arazzo Step Objects
        $wfId = $mName;
        if (preg_match('/@arazzo-workflow-id\s+(\S+)/', $doc, $idMatch)) {
            $wfId = $idMatch[1];
        }
        /** @var MethodCall[] $calls */
        $calls = $nodeFinder->findInstanceOf($method->stmts ?? [], MethodCall::class);
        $steps = [];
        $stepNum = 1;
        foreach ($calls as $call) {
            if ($call->name instanceof Identifier) {
                $calledName = $call->name->toString();
                if ($calledName === 'executeWorkflow' && isset($call->args[0]) && $call->args[0]->value instanceof String_) {
                    $steps[] = [
                        'stepId' => 'step_' . $stepNum++,
                        'workflowId' => $call->args[0]->value->value,
                    ];
                } elseif (!in_array($calledName, $skipMethods, true)) {
                    $steps[] = [
                        'stepId' => 'step_' . $stepNum++,
                        'operationId' => $calledName,
                        'successCriteria' => [
                            ['condition' => '$statusCode == 200'],
                        ],
                    ];
                }
            }
        }
        if (!empty($steps)) {
            $arazzo['workflows'][] = [
                'workflowId' => $wfId,
                'steps' => $steps,
            ];
        }
    }

    return $arazzo;
}
