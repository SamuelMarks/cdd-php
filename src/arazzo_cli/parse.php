<?php

/**
 * Parses PHP CLI workflow runner scripts (`arazzo_cli.php`) back into Arazzo 1.1.0 specifications.
 *
 * @package Cdd\ArazzoCli
 */

declare(strict_types=1);

namespace Cdd\ArazzoCli;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeFinder;

/**
 * Parses a generated or hand-edited PHP CLI workflow runner into an Arazzo 1.1.0 array.
 *
 * @param string $cliCode The `arazzo_cli.php` source code.
 * @param string|null $sdkWorkflowsCode Optional companion `ApiWorkflows.php` source code.
 * @return array<string, mixed> Reconstructed Arazzo 1.1.0 specification array.
 */
function parse(string $cliCode, ?string $sdkWorkflowsCode = null): array
{
    if ($sdkWorkflowsCode !== null && $sdkWorkflowsCode !== '') {
        $fromSdk = \Cdd\ArazzoClient\parse($sdkWorkflowsCode);
        if (!empty($fromSdk) && !empty($fromSdk['workflows'])) {
            return $fromSdk;
        }
    }

    $parser = \Cdd\Classes\create_parser();
    $stmts = null;
    try {
        $stmts = $parser->parse($cliCode);
    } catch (\Throwable $e) {
        $stmts = null;
    }

    $arazzo = [
        'arazzo' => '1.1.0',
        'info' => [
            'title' => 'CLI Workflows',
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

    $extraMeta = [];
    if ($stmts !== null && !empty($stmts)) {
        $nodeFinder = new NodeFinder();
        /** @var Assign[] $assigns */
        $assigns = $nodeFinder->findInstanceOf($stmts, Assign::class);
        foreach ($assigns as $assign) {
            if ($assign->var instanceof Variable && $assign->var->name === 'cliMetadata' && $assign->expr instanceof Array_) {
                $meta = \Cdd\Classes\evaluate_ast_node($assign->expr);
                if (is_array($meta)) {
                    $extraMeta = $meta;
                }
            }
        }

        if (isset($extraMeta['arazzo']) && is_string($extraMeta['arazzo'])) {
            $arazzo['arazzo'] = $extraMeta['arazzo'];
        }
        $ordered = ['arazzo' => $arazzo['arazzo']];
        if (isset($extraMeta['$self']) && is_string($extraMeta['$self'])) {
            $ordered['$self'] = $extraMeta['$self'];
        }
        foreach ($extraMeta as $k => $v) {
            if (is_string($k) && str_starts_with($k, 'x-')) {
                $ordered[$k] = $v;
            }
        }
        $ordered['info'] = isset($extraMeta['info']) && is_array($extraMeta['info']) ? $extraMeta['info'] : $arazzo['info'];
        $ordered['sourceDescriptions'] = isset($extraMeta['sourceDescriptions']) && is_array($extraMeta['sourceDescriptions']) ? $extraMeta['sourceDescriptions'] : $arazzo['sourceDescriptions'];
        $ordered['workflows'] = [];
        $arazzo = $ordered;

        /** @var If_[] $ifs */
        $ifs = $nodeFinder->findInstanceOf($stmts, If_::class);
        foreach ($ifs as $ifNode) {
            if ($ifNode->cond instanceof Identical
                && $ifNode->cond->left instanceof Variable
                && $ifNode->cond->left->name === 'command'
                && $ifNode->cond->right instanceof String_
            ) {
                $wfId = $ifNode->cond->right->value;
                if (in_array($wfId, ['--help', '-h', '--version', '-V', 'mcp'], true)) {
                    continue;
                }

                $foundWfDef = null;
                foreach ($ifNode->stmts as $stmt) {
                    if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                        $assign = $stmt->expr;
                        if ($assign->var instanceof Variable && $assign->var->name === 'workflowDefinition' && $assign->expr instanceof Array_) {
                            $val = \Cdd\Classes\evaluate_ast_node($assign->expr);
                            if (is_array($val) && isset($val['workflowId'])) {
                                $foundWfDef = $val;
                                break;
                            }
                        }
                    }
                }

                if ($foundWfDef !== null) {
                    $arazzo['workflows'][] = $foundWfDef;
                } else {
                    $arazzo['workflows'][] = [
                        'workflowId' => $wfId,
                        'steps' => [
                            [
                                'stepId' => 'run_' . $wfId,
                                'operationId' => $wfId,
                            ],
                        ],
                    ];
                }
            }
        }

        if (isset($extraMeta['components']) && is_array($extraMeta['components'])) {
            $arazzo['components'] = $extraMeta['components'];
        }
    }

    return $arazzo;
}
