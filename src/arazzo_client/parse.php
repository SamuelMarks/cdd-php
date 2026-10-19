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
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
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
    return \Cdd\Classes\evaluate_ast_node($expr);
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
    /** @var ClassConst[] $classConsts */
    $classConsts = $nodeFinder->findInstanceOf($stmts, ClassConst::class);
    /** @var ClassMethod[] $methods */
    $methods = $nodeFinder->findInstanceOf($stmts, ClassMethod::class);

    $constants = [];
    foreach ($classConsts as $constStmt) {
        foreach ($constStmt->consts as $c) {
            $constants[$c->name->toString()] = astExprToPhpValue($c->value);
        }
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
        'title' => 'Extracted Workflows',
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
    $arazzo['workflows'] = [];

    if (!isset($constants['ARAZZO_VERSION']) && preg_match('/@arazzo-version\s+(\S+)/', $code, $m)) {
        $arazzo['arazzo'] = $m[1];
    }
    if (!isset($arazzo['$self']) && preg_match('/@arazzo-self\s+(\S+)/', $code, $m)) {
        $arazzo['$self'] = $m[1];
    }
    if (!isset($constants['INFO'])) {
        if (preg_match('/@title\s+([^
]+)/', $code, $m)) {
            $arazzo['info']['title'] = stripslashes(trim($m[1]));
        }
        if (preg_match('/@version\s+([^
]+)/', $code, $m)) {
            $arazzo['info']['version'] = stripslashes(trim($m[1]));
        }
        if (preg_match('/@summary\s+([^
]+)/', $code, $m)) {
            $arazzo['info']['summary'] = trim($m[1]);
        }
        if (preg_match('/@description\s+([^
]+)/', $code, $m)) {
            $arazzo['info']['description'] = trim($m[1]);
        }
    }
    if (!isset($constants['SOURCE_DESCRIPTIONS']) && preg_match_all('/@arazzo-source\s+(\{[^
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

    $skipMethods = ['__construct', 'getWorkflowRegistry', 'executeWorkflow', 'runWorkflowDefinition', 'matchesActionCriteria', 'dispatchStepOperation', 'setApiKey', 'setBearerToken', 'requireSecurity', 'mcp', 'connect_mcp', 'connect_mcp_sse', 'workflows'];

    // First check if getWorkflowRegistry() AST method defines workflows
    foreach ($methods as $method) {
        if ($method->name->toString() === 'getWorkflowRegistry' && $method->stmts !== null) {
            foreach ($method->stmts as $stmt) {
                if ($stmt instanceof Return_ && $stmt->expr instanceof Array_) {
                    $registry = astExprToPhpValue($stmt->expr);
                    if (is_array($registry) && !empty($registry)) {
                        $arazzo['workflows'] = array_values($registry);
                    }
                }
            }
        }
    }

    // If getWorkflowRegistry was not present, inspect public workflow methods AST
    if (empty($arazzo['workflows'])) {
        foreach ($methods as $method) {
            $mName = $method->name->toString();
            if (!$method->isPublic() || in_array($mName, $skipMethods, true)) {
                continue;
            }
            $doc = $method->getDocComment()?->getText() ?? '';

            // Check for AST `$workflow = [...];` assignment inside the workflow method body
            $extractedFromVar = null;
            foreach ($method->stmts ?? [] as $stmt) {
                if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                    $assign = $stmt->expr;
                    if ($assign->var instanceof Variable && $assign->var->name === 'workflow' && $assign->expr instanceof Array_) {
                        $val = astExprToPhpValue($assign->expr);
                        if (is_array($val) && isset($val['workflowId'])) {
                            $extractedFromVar = $val;
                            break;
                        }
                    }
                }
            }
            if ($extractedFromVar !== null) {
                $arazzo['workflows'][] = $extractedFromVar;
                continue;
            }

            if (preg_match('/@arazzo-workflow\s+(\{[^
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
    }

    if (isset($constants['COMPONENTS']) && is_array($constants['COMPONENTS']) && !empty($constants['COMPONENTS'])) {
        $arazzo['components'] = $constants['COMPONENTS'];
    } elseif (preg_match('/@arazzo-components\s+(\{[^\r\n]+\})/', $code, $compMatch)) {
        $decodedComp = json_decode($compMatch[1], true);
        if (is_array($decodedComp)) {
            $arazzo['components'] = $decodedComp;
        }
    }

    return $arazzo;
}
