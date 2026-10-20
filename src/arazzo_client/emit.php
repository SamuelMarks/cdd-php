<?php

/**
 * Emits PHP client SDK workflow orchestrator classes (`ApiWorkflows.php`) and input DTOs from Arazzo specifications.
 *
 * @package Cdd\ArazzoClient
 */

declare(strict_types=1);

namespace Cdd\ArazzoClient;

/**
 * Converts a workflowId or source name into a valid camelCase PHP identifier.
 *
 * @param string $identifier Raw identifier string.
 * @return string Valid camelCase PHP identifier.
 */
function toCamelCaseIdentifier(string $identifier): string
{
    $cleaned = (string) preg_replace('/[^a-zA-Z0-9]+/', ' ', $identifier);
    $words = array_values(array_filter(explode(' ', trim($cleaned)), static fn (string $w): bool => $w !== ''));
    if (empty($words)) {
        return 'workflow';
    }
    $first = lcfirst($words[0]);
    $rest = array_map(static fn (string $w): string => ucfirst($w), array_slice($words, 1));
    $res = $first . implode('', $rest);
    return preg_match('/^[0-9]/', $res) ? 'wf' . ucfirst($res) : $res;
}

/**
 * Emits PHP DTO model classes for workflow `inputs` and `components.inputs` JSON Schemas.
 *
 * @param array<string, mixed> $arazzo The normalized Arazzo specification array.
 * @return string Generated PHP code for `WorkflowInputs.php`.
 */
function emitInputDtos(array $arazzo): string
{
    $schemas = [];
    if (isset($arazzo['components']['inputs']) && is_array($arazzo['components']['inputs'])) {
        foreach ($arazzo['components']['inputs'] as $name => $schema) {
            if (is_array($schema)) {
                $className = ucfirst(toCamelCaseIdentifier((string) $name)) . 'Input';
                $schemas[$className] = $schema;
            }
        }
    }
    if (isset($arazzo['workflows']) && is_array($arazzo['workflows'])) {
        foreach ($arazzo['workflows'] as $wf) {
            if (isset($wf['inputs']) && is_array($wf['inputs']) && isset($wf['inputs']['properties'])) {
                $wfId = (string) ($wf['workflowId'] ?? 'workflow');
                $className = ucfirst(toCamelCaseIdentifier($wfId)) . 'Input';
                $schemas[$className] = $wf['inputs'];
            }
        }
    }
    return \Cdd\Components\emit(['schemas' => $schemas]);
}

/**
 * Emits the `ApiWorkflows` (or `WorkflowEngine`) PHP SDK orchestrator class from a validated Arazzo specification.
 *
 * @param array<string, mixed> $arazzo The normalized Arazzo 1.1.0 specification array.
 * @param string $className The class name to emit (`ApiWorkflows` or `WorkflowEngine`).
 * @return string The generated workflow orchestrator PHP source code.
 */
function emit(array $arazzo, string $className = 'ApiWorkflows'): string
{
    $version = (string) ($arazzo['arazzo'] ?? '1.1.0');
    $info = is_array($arazzo['info'] ?? null) ? $arazzo['info'] : ['title' => 'Arazzo Workflows', 'version' => '1.0.0'];
    $sources = is_array($arazzo['sourceDescriptions'] ?? null) ? $arazzo['sourceDescriptions'] : [];
    $workflows = is_array($arazzo['workflows'] ?? null) ? $arazzo['workflows'] : [];
    $components = is_array($arazzo['components'] ?? null) ? $arazzo['components'] : [];
    $rootExt = \Cdd\Arazzo\extractExtensions($arazzo);

    $title = addslashes((string) ($info['title'] ?? 'Arazzo Workflows'));
    $infoVersion = addslashes((string) ($info['version'] ?? '1.0.0'));

    $out = "<?php

";
    $out .= "/**
";
    $out .= " * Auto-generated Arazzo Workflow Orchestrator SDK
";
    $out .= " *
";
    $out .= " * @package Api
";
    $out .= " * @arazzo-version {$version}
";
    if (isset($arazzo['$self']) && is_string($arazzo['$self'])) {
        $out .= " * @arazzo-self " . $arazzo['$self'] . "
";
    }
    $out .= " * @title {$title}
";
    $out .= " * @version {$infoVersion}
";
    if (isset($info['summary']) && is_string($info['summary'])) {
        $out .= " * @summary " . str_replace("
", ' ', $info['summary']) . "
";
    }
    if (isset($info['description']) && is_string($info['description'])) {
        $out .= " * @description " . str_replace("
", ' ', $info['description']) . "
";
    }
    foreach (\Cdd\Arazzo\extractExtensions($info) as $extK => $extV) {
        $out .= " * @arazzo-info-ext {$extK} " . json_encode($extV, JSON_UNESCAPED_SLASHES) . "
";
    }
    foreach ($sources as $src) {
        $out .= " * @arazzo-source " . json_encode($src, JSON_UNESCAPED_SLASHES) . "
";
    }
    if (!empty($components)) {
        $out .= " * @arazzo-components " . json_encode($components, JSON_UNESCAPED_SLASHES) . "
";
    }
    foreach ($rootExt as $extK => $extV) {
        $out .= " * @arazzo-ext {$extK} " . json_encode($extV, JSON_UNESCAPED_SLASHES) . "
";
    }
    $out .= " */

";
    $out .= "declare(strict_types=1);

";
    $out .= "namespace Api;

";
    $out .= "use function Cdd\\Arazzo\\applyPayloadReplacements;
";
    $out .= "use function Cdd\\Arazzo\\evaluateCriterion;
";
    $out .= "use function Cdd\\Arazzo\\evaluateRuntimeExpression;
";
    $out .= "use function Cdd\\Arazzo\\resolveReusableObject;

";
    $out .= "/**
 * {$className} orchestrates multi-step Arazzo workflows across OpenAPI, AsyncAPI, and Arazzo sources.
 */
";
    $out .= "class {$className}
{
";
    $out .= "    /** @var string Arazzo specification version. */
";
    $out .= "    public const ARAZZO_VERSION = " . var_export($version, true) . ";

";
    if (isset($arazzo['$self']) && is_string($arazzo['$self'])) {
        $out .= "    /** @var string|null Self URI of the Arazzo document. */
";
        $out .= "    public const SELF_URI = " . var_export($arazzo['$self'], true) . ";

";
    } else {
        $out .= "    /** @var string|null Self URI of the Arazzo document. */
";
        $out .= "    public const SELF_URI = null;

";
    }
    $out .= "    /** @var array<string, mixed> Info Object metadata. */
";
    $out .= "    public const INFO = " . var_export($info, true) . ";

";
    $out .= "    /** @var array<int, array<string, mixed>> Source Description Objects. */
";
    $out .= "    public const SOURCE_DESCRIPTIONS = " . var_export($sources, true) . ";

";
    $out .= "    /** @var array<string, mixed> Reusable Arazzo Components Object. */
";
    $out .= "    public const COMPONENTS = " . var_export($components, true) . ";

";
    $out .= "    /** @var array<string, mixed> Root specification extensions. */
";
    $out .= "    public const EXTENSIONS = " . var_export($rootExt, true) . ";

";

    $out .= "    /** @var mixed Primary HTTP/SDK client or callable transport. */
";
    $out .= "    public mixed \$client;

";
    $out .= "    /** @var array<string, mixed> Map of sourceDescription name to client/transport instance. */
";
    $out .= "    public array \$sources = [];

";
    $out .= "    /** @var array<string, array<string, mixed>> Completed workflow execution states (`inputs` and `outputs`). */
";
    $out .= "    public array \$workflowState = [];

";
    $out .= "    /** @var callable|null Optional step progress listener `fn(string \$workflowId, array \$step, array \$context): void`. */
";
    $out .= "    public \$onStep = null;

";

    foreach ($sources as $src) {
        $srcName = toCamelCaseIdentifier((string) ($src['name'] ?? 'default'));
        $srcType = (string) ($src['type'] ?? 'openapi');
        $out .= "    /** @var mixed Client binding for {$srcType} source '{$src['name']}' ({$src['url']}). */
";
        $out .= "    public mixed \${$srcName}Client = null;

";
    }

    $workflowMethodMap = [];
    foreach ($workflows as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $workflowMethodMap[$wfId] = toCamelCaseIdentifier($wfId);
    }

    $out .= "    /**
     * Returns the workflow definition registry mapped by workflowId.
     *
     * @return array<string, array<string, mixed>>
     */
";
    $out .= "    protected function getWorkflowRegistry(): array
    {
";
    $out .= "        return [
";
    foreach ($workflows as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $wfExport = var_export($wf, true);
        $out .= "            " . var_export($wfId, true) . " => {$wfExport},
";
    }
    $out .= "        ];
";
    $out .= "    }

";

    $out .= "    /**
     * Initializes the workflow orchestrator.
     *
     * @param mixed \$client Primary SDK client or callable `(string \$operation, array \$params, mixed \$body, array \$headers, array \$step): array`.
     * @param array<string, mixed> \$sources Optional map of named source clients.
     */
";
    $out .= "    public function __construct(mixed \$client = null, array \$sources = [])
    {
";
    $out .= "        \$this->client = \$client;
";
    $out .= "        \$this->sources = \$sources;
";
    foreach ($sources as $src) {
        $rawName = (string) ($src['name'] ?? 'default');
        $propName = toCamelCaseIdentifier($rawName) . 'Client';
        $out .= "        \$this->{$propName} = \$sources['{$rawName}'] ?? \$client;
";
    }
    $out .= "    }

";

    $out .= <<<'PHP'
    /**
     * Executes a named workflow by workflowId.
     *
     * @param string $workflowId The target workflowId.
     * @param array<string, mixed> $inputs Input parameters map.
     * @param string|null $onlyStepId Optional single stepId filter.
     * @param bool $dryRun When true, returns the planned step sequence without network execution.
     * @return array<string, mixed> Workflow outputs map.
     */
    public function executeWorkflow(string $workflowId, array $inputs = [], ?string $onlyStepId = null, bool $dryRun = false): array
    {
        $workflowsById = $this->getWorkflowRegistry();
        if (!isset($workflowsById[$workflowId])) {
            throw new \RuntimeException("Unknown workflowId: {$workflowId}");
        }
        return $this->runWorkflowDefinition($workflowsById[$workflowId], $inputs, $onlyStepId, $dryRun);
    }

    /**
     * Executes a structured Arazzo Workflow Object definition.
     *
     * @param array<string, mixed> $workflow The Workflow Object definition.
     * @param array<string, mixed> $inputs Input parameters map.
     * @param string|null $onlyStepId Optional single stepId filter.
     * @param bool $dryRun When true, returns the planned step sequence without network execution.
     * @return array<string, mixed> Workflow outputs map.
     */
    protected function runWorkflowDefinition(array $workflow, array $inputs = [], ?string $onlyStepId = null, bool $dryRun = false): array
    {
        $workflowId = (string) ($workflow['workflowId'] ?? 'workflow');

        if (isset($workflow['dependsOn']) && is_array($workflow['dependsOn'])) {
            foreach ($workflow['dependsOn'] as $depWfId) {
                $depKey = (string) $depWfId;
                if (!str_starts_with($depKey, '$sourceDescriptions.') && !isset($this->workflowState[$depKey])) {
                    $this->executeWorkflow($depKey, $inputs, null, $dryRun);
                }
            }
        }

        if ($dryRun) {
            $planned = array_map(static fn (array $s): string => (string) $s['stepId'], $workflow['steps'] ?? []);
            return ['dryRun' => true, 'workflowId' => $workflowId, 'plannedSteps' => $planned, 'inputs' => $inputs];
        }

        $sourceMap = [];
        foreach (static::SOURCE_DESCRIPTIONS as $sd) {
            $sourceMap[(string) $sd['name']] = $sd;
        }
        $components = static::COMPONENTS;

        $context = [
            'self' => static::SELF_URI ?? '',
            'inputs' => $inputs,
            'outputs' => [],
            'steps' => [],
            'workflows' => $this->workflowState,
            'sourceDescriptions' => $sourceMap,
            'components' => $components,
            'url' => '',
            'method' => 'GET',
            'statusCode' => 200,
            'request' => ['headers' => [], 'query' => [], 'path' => [], 'body' => null],
            'response' => ['headers' => [], 'body' => null],
            'message' => ['headers' => [], 'payload' => null],
        ];

        $wfParams = is_array($workflow['parameters'] ?? null) ? $workflow['parameters'] : [];
        $steps = array_values(is_array($workflow['steps'] ?? null) ? $workflow['steps'] : []);
        $stepIndexById = [];
        foreach ($steps as $idx => $st) {
            $stepIndexById[(string) $st['stepId']] = $idx;
        }

        $retryCounts = [];
        $idx = 0;
        $totalSteps = count($steps);

        while ($idx < $totalSteps) {
            $step = $steps[$idx];
            $stepId = (string) $step['stepId'];
            if ($onlyStepId !== null && $stepId !== $onlyStepId) {
                $idx++;
                continue;
            }

            if (isset($step['dependsOn']) && is_array($step['dependsOn'])) {
                foreach ($step['dependsOn'] as $prereqStepId) {
                    if (!isset($context['steps'][(string) $prereqStepId]) && isset($stepIndexById[(string) $prereqStepId]) && $stepIndexById[(string) $prereqStepId] > $idx) {
                        $idx = $stepIndexById[(string) $prereqStepId];
                        continue 2;
                    }
                }
            }

            if (is_callable($this->onStep)) {
                ($this->onStep)($workflowId, $step, $context);
            }

            $mergedParams = array_merge($wfParams, is_array($step['parameters'] ?? null) ? $step['parameters'] : []);
            $reqHeaders = [];
            $reqQuery = [];
            $reqPath = [];
            $subWfInputs = [];

            foreach ($mergedParams as $rawParam) {
                $p = isset($rawParam['reference']) && function_exists('Cdd\Arazzo\resolveReusableObject')
                    ? resolveReusableObject($rawParam, $components, 'parameters')
                    : $rawParam;
                $pName = (string) ($p['name'] ?? '');
                $pIn = (string) ($p['in'] ?? 'query');
                $pVal = function_exists('Cdd\Arazzo\evaluateRuntimeExpression')
                    ? evaluateRuntimeExpression($p['value'] ?? null, $context)
                    : ($p['value'] ?? null);
                $subWfInputs[$pName] = $pVal;
                if ($pIn === 'header') {
                    $reqHeaders[$pName] = $pVal;
                } elseif ($pIn === 'path') {
                    $reqPath[$pName] = $pVal;
                } else {
                    $reqQuery[$pName] = $pVal;
                }
            }

            $reqBody = null;
            if (isset($step['requestBody']) && is_array($step['requestBody'])) {
                $rawPayload = $step['requestBody']['payload'] ?? null;
                $reps = is_array($step['requestBody']['replacements'] ?? null) ? $step['requestBody']['replacements'] : [];
                $reqBody = function_exists('Cdd\Arazzo\applyPayloadReplacements')
                    ? applyPayloadReplacements($rawPayload, $reps, $context)
                    : $rawPayload;
            }

            $context['request'] = [
                'headers' => $reqHeaders,
                'query' => $reqQuery,
                'path' => $reqPath,
                'body' => $reqBody,
            ];

            $stepPassed = true;
            $stepOutputs = [];
            if (isset($step['workflowId'])) {
                $targetWf = (string) $step['workflowId'];
                $subOutputs = $this->executeWorkflow($targetWf, !empty($subWfInputs) ? $subWfInputs : $inputs);
                $context['statusCode'] = 200;
                $context['response'] = ['headers' => [], 'body' => $subOutputs];
                $stepOutputs = $subOutputs;
            } else {
                $opTarget = (string) ($step['operationId'] ?? $step['operationPath'] ?? $step['channelPath'] ?? '');
                $dispatchRes = $this->dispatchStepOperation($opTarget, $reqPath + $reqQuery, $reqBody, $reqHeaders, $step);
                $context['url'] = (string) ($dispatchRes['url'] ?? $opTarget);
                $context['method'] = (string) ($dispatchRes['method'] ?? ($step['action'] ?? 'GET'));
                $context['statusCode'] = (int) ($dispatchRes['status'] ?? 200);
                $context['response'] = [
                    'headers' => (array) ($dispatchRes['headers'] ?? []),
                    'body' => $dispatchRes['data'] ?? null,
                ];
                $context['message'] = [
                    'headers' => (array) ($dispatchRes['headers'] ?? []),
                    'payload' => $dispatchRes['data'] ?? null,
                ];
            }

            if (isset($step['successCriteria']) && is_array($step['successCriteria'])) {
                foreach ($step['successCriteria'] as $criterion) {
                    if (function_exists('Cdd\Arazzo\evaluateCriterion') && !evaluateCriterion($criterion, $context)) {
                        $stepPassed = false;
                        break;
                    }
                }
            }

            if ($stepPassed && isset($step['outputs']) && is_array($step['outputs'])) {
                foreach ($step['outputs'] as $outKey => $outExpr) {
                    $stepOutputs[(string) $outKey] = function_exists('Cdd\Arazzo\evaluateRuntimeExpression')
                        ? evaluateRuntimeExpression($outExpr, $context)
                        : $outExpr;
                }
            }

            $context['steps'][$stepId] = [
                'statusCode' => $context['statusCode'],
                'outputs' => $stepOutputs,
                'response' => $context['response'],
            ];

            if ($stepPassed) {
                $actions = array_merge(
                    is_array($step['onSuccess'] ?? null) ? $step['onSuccess'] : [],
                    is_array($workflow['successActions'] ?? null) ? $workflow['successActions'] : []
                );
                $jumped = false;
                foreach ($actions as $rawAct) {
                    $act = isset($rawAct['reference']) && function_exists('Cdd\Arazzo\resolveReusableObject')
                        ? resolveReusableObject($rawAct, $components, 'successActions')
                        : $rawAct;
                    if (!$this->matchesActionCriteria($act, $context)) {
                        continue;
                    }
                    if (($act['type'] ?? '') === 'end') {
                        break 2;
                    }
                    if (($act['type'] ?? '') === 'goto') {
                        if (isset($act['stepId']) && isset($stepIndexById[(string) $act['stepId']])) {
                            $idx = $stepIndexById[(string) $act['stepId']];
                            $jumped = true;
                            break;
                        }
                        if (isset($act['workflowId'])) {
                            return $this->executeWorkflow((string) $act['workflowId'], $inputs);
                        }
                    }
                }
                if (!$jumped) {
                    $idx++;
                }
            } else {
                $fActions = array_merge(
                    is_array($step['onFailure'] ?? null) ? $step['onFailure'] : [],
                    is_array($workflow['failureActions'] ?? null) ? $workflow['failureActions'] : []
                );
                $handled = false;
                foreach ($fActions as $rawAct) {
                    $act = isset($rawAct['reference']) && function_exists('Cdd\Arazzo\resolveReusableObject')
                        ? resolveReusableObject($rawAct, $components, 'failureActions')
                        : $rawAct;
                    if (!$this->matchesActionCriteria($act, $context)) {
                        continue;
                    }
                    $fType = (string) ($act['type'] ?? 'end');
                    if ($fType === 'retry') {
                        $limit = (int) ($act['retryLimit'] ?? 1);
                        $retryKey = $stepId . ':' . ($act['name'] ?? 'retry');
                        $used = $retryCounts[$retryKey] ?? 0;
                        if ($used < $limit) {
                            $retryCounts[$retryKey] = $used + 1;
                            if (isset($act['stepId']) && isset($stepIndexById[(string) $act['stepId']])) {
                                $idx = $stepIndexById[(string) $act['stepId']];
                            }
                            $handled = true;
                            break;
                        }
                    } elseif ($fType === 'goto') {
                        if (isset($act['stepId']) && isset($stepIndexById[(string) $act['stepId']])) {
                            $idx = $stepIndexById[(string) $act['stepId']];
                            $handled = true;
                            break;
                        }
                        if (isset($act['workflowId'])) {
                            return $this->executeWorkflow((string) $act['workflowId'], $inputs);
                        }
                    } elseif ($fType === 'end') {
                        throw new \RuntimeException("Workflow '{$workflowId}' terminated at step '{$stepId}' due to failure action '{$act['name']}'");
                    }
                }
                if (!$handled) {
                    throw new \RuntimeException("Step '{$stepId}' failed successCriteria in workflow '{$workflowId}'");
                }
            }
            }

            $finalOutputs = [];
            if (isset($workflow['outputs']) && is_array($workflow['outputs'])) {
            foreach ($workflow['outputs'] as $outName => $outExpr) {
                $finalOutputs[(string) $outName] = function_exists('Cdd\Arazzo\evaluateRuntimeExpression')
                    ? evaluateRuntimeExpression($outExpr, $context)
                    : $outExpr;
            }
            }
            $context['outputs'] = $finalOutputs;
            $this->workflowState[$workflowId] = [
            'inputs' => $inputs,
            'outputs' => $finalOutputs,
            ];

            return $finalOutputs;
            }

            /**
            * Checks whether all criteria on a Success or Failure Action pass.
            *
            * @param array<string, mixed> $action The resolved Action Object.
            * @param array<string, mixed> $context Runtime context.
            * @return bool True if criteria are absent or all evaluate to true.
            */
            protected function matchesActionCriteria(array $action, array $context): bool
            {
            if (!isset($action['criteria']) || !is_array($action['criteria'])) {
            return true;
            }
            foreach ($action['criteria'] as $c) {
            if (function_exists('Cdd\Arazzo\evaluateCriterion') && !evaluateCriterion($c, $context)) {
                return false;
            }
            }
            return true;
            }

    /**
     * Dispatches a single step operation against the configured client or callable transport.
     *
     * @param string $opTarget Operation identifier, operationPath, or channelPath.
     * @param array<string, mixed> $params Merged path and query parameters.
     * @param mixed $body Evaluated request body payload.
     * @param array<string, mixed> $headers Evaluated request headers.
     * @param array<string, mixed> $step Full step definition.
     * @return array<string, mixed> Normalized response `['status' => int, 'headers' => array, 'data' => mixed, 'url' => string, 'method' => string]`.
     */
    protected function dispatchStepOperation(string $opTarget, array $params, mixed $body, array $headers, array $step): array
    {
        $targetClient = $this->client;
        if (str_contains($opTarget, '.')) {
            [$maybeSource, $shortOp] = explode('.', $opTarget, 2);
            if (isset($this->sources[$maybeSource])) {
                $targetClient = $this->sources[$maybeSource];
                $opTarget = $shortOp;
            }
        }
        if (is_callable($targetClient)) {
            $res = ($targetClient)($opTarget, $params, $body, $headers, $step);
            if (is_array($res) && isset($res['status'])) {
                return $res;
            }
            return ['status' => 200, 'headers' => [], 'data' => $res, 'url' => $opTarget, 'method' => 'POST'];
        }
        if (is_object($targetClient) && method_exists($targetClient, $opTarget)) {
            $res = $targetClient->{$opTarget}($params, is_array($body) ? $body : []);
            return is_array($res) ? $res : ['status' => 200, 'headers' => [], 'data' => $res];
        }
        return ['status' => 200, 'headers' => $headers, 'data' => $body ?? $params, 'url' => $opTarget, 'method' => 'GET'];
    }
PHP;

    foreach ($workflows as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $methodName = $workflowMethodMap[$wfId];
        $summary = (string) ($wf['summary'] ?? "Executes workflow {$wfId}.");
        $desc = (string) ($wf['description'] ?? '');
        $wfExport = var_export($wf, true);

        $out .= "
    /**
";
        $out .= "     * {$summary}
";
        if ($desc !== '') {
            $out .= "     * {$desc}
";
        }
        $out .= "     *
";
        $out .= "     * @arazzo-workflow-id {$wfId}
";
        $out .= "     * @param array<string, mixed> \$inputs Workflow input parameters.
";
        $out .= "     * @param string|null \$onlyStepId Optional single stepId filter.
";
        $out .= "     * @param bool \$dryRun When true, returns the planned step sequence without network execution.
";
        $out .= "     * @return array<string, mixed> Extracted workflow outputs.
";
        $out .= "     */
";
        $out .= "    public function {$methodName}(array \$inputs = [], ?string \$onlyStepId = null, bool \$dryRun = false): array
";
        $out .= "    {
";
        $out .= "        \$workflow = {$wfExport};
";
        $out .= "        return \$this->runWorkflowDefinition(\$workflow, \$inputs, \$onlyStepId, \$dryRun);
";
        $out .= "    }
";
    }

    $out .= "}
";
    return $out;
}
