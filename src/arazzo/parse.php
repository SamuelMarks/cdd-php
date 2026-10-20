<?php

/**
 * Parses and validates Arazzo 1.0.x and 1.1.0 workflow specification documents.
 *
 * @package Cdd\Arazzo
 */

declare(strict_types=1);

namespace Cdd\Arazzo;

/**
 * Extracts all specification extensions (`^x-`) from an associative array.
 *
 * @param array<string, mixed> $obj The object map to inspect.
 * @return array<string, mixed> Map of extension keys to values.
 */
function extractExtensions(array $obj): array
{
    $ext = [];
    foreach ($obj as $key => $val) {
        if (str_starts_with((string) $key, 'x-')) {
            $ext[(string) $key] = $val;
        }
    }
    return $ext;
}

/**
 * Validates an Arazzo Info Object.
 *
 * @param mixed $info The Info Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateArazzoInfoObject(mixed $info): void
{
    if (!is_array($info)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Arazzo "info" must be an object');
    }
    if (!isset($info['title']) || !is_string($info['title']) || $info['title'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Missing or invalid REQUIRED field "title" in Arazzo Info Object');
    }
    if (!isset($info['version']) || !is_string($info['version']) || $info['version'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Missing or invalid REQUIRED field "version" in Arazzo Info Object');
    }
    if (isset($info['summary']) && !is_string($info['summary'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "summary" in Arazzo Info Object must be a string');
    }
    if (isset($info['description']) && !is_string($info['description'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "description" in Arazzo Info Object must be a string');
    }
}

/**
 * Validates a single Arazzo Source Description Object.
 *
 * @param mixed $source The Source Description Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateSourceDescriptionObject(mixed $source): void
{
    if (!is_array($source)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Each item in "sourceDescriptions" must be an object');
    }
    if (!isset($source['name']) || !is_string($source['name']) || !preg_match('/^[A-Za-z0-9_\-]+$/', $source['name'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Source Description "name" is REQUIRED and must match ^[A-Za-z0-9_\-]+$');
    }
    if (!isset($source['url']) || !is_string($source['url']) || $source['url'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Source Description "url" is REQUIRED and must be a non-empty string');
    }
    if (isset($source['type'])) {
        if (!is_string($source['type']) || !in_array($source['type'], ['openapi', 'asyncapi', 'arazzo'], true)) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Source Description "type" must be one of: openapi, asyncapi, arazzo');
        }
    }
}

/**
 * Validates the root `sourceDescriptions` list and ensures unique names.
 *
 * @param mixed $sources The list of Source Description Objects.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateSourceDescriptions(mixed $sources): void
{
    if (!is_array($sources) || empty($sources)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "sourceDescriptions" is REQUIRED and must be a non-empty list');
    }
    $seenNames = [];
    foreach ($sources as $source) {
        validateSourceDescriptionObject($source);
        $name = (string) $source['name'];
        if (isset($seenNames[$name])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Duplicate sourceDescription name: $name");
        }
        $seenNames[$name] = true;
    }
}

/**
 * Resolves and loads local source descriptions relative to a base directory.
 *
 * @param array<int, array<string, mixed>> $sourceDescriptions The validated sourceDescriptions list.
 * @param string $baseDir Base directory to resolve relative paths against.
 * @return array<string, array<string, mixed>> Map of source name to parsed specification array.
 */
function loadSourceDescriptions(array $sourceDescriptions, string $baseDir): array
{
    $loaded = [];
    foreach ($sourceDescriptions as $src) {
        $name = (string) ($src['name'] ?? '');
        $url = (string) ($src['url'] ?? '');
        $type = (string) ($src['type'] ?? 'openapi');
        if ($name === '' || $url === '' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            continue;
        }
        $candidate = str_starts_with($url, '/') ? $url : rtrim($baseDir, '/') . '/' . ltrim($url, './');
        if (file_exists($candidate) && is_file($candidate)) {
            $raw = (string) file_get_contents($candidate);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                if ($type === 'openapi' && (isset($decoded['openapi']) || isset($decoded['swagger']))) {
                    $loaded[$name] = \Cdd\Openapi\parse($raw);
                } elseif ($type === 'arazzo' && isset($decoded['arazzo'])) {
                    $loaded[$name] = parse($raw);
                } else {
                    $loaded[$name] = $decoded;
                }
            }
        }
    }
    return $loaded;
}

/**
 * Validates an Arazzo Expression Type Object (`type` and `version`).
 *
 * @param mixed $exprType The Expression Type Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateExpressionTypeObject(mixed $exprType): void
{
    if (!is_array($exprType)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Expression Type Object must be an array/object');
    }
    $type = $exprType['type'] ?? null;
    if (!is_string($type) || !in_array($type, ['jsonpath', 'xpath', 'jsonpointer'], true)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Expression Type Object "type" must be one of: jsonpath, xpath, jsonpointer');
    }
    $version = $exprType['version'] ?? null;
    $validVersions = [
        'jsonpath' => ['rfc9535', 'draft-goessner-dispatch-jsonpath-00'],
        'xpath' => ['xpath-31', 'xpath-20', 'xpath-10'],
        'jsonpointer' => ['rfc6901'],
    ];
    if (!is_string($version) || !in_array($version, $validVersions[$type], true)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation("Invalid version for Expression Type '$type'");
    }
}

/**
 * Validates an Arazzo 1.1.0 Selector Object (`context`, `selector`, `type`).
 *
 * @param mixed $selector The Selector Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateSelectorObject(mixed $selector): void
{
    if (!is_array($selector)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Selector Object must be an array/object');
    }
    if (!isset($selector['context']) || !is_string($selector['context']) || $selector['context'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Selector Object requires a non-empty string "context"');
    }
    if (!isset($selector['selector']) || !is_string($selector['selector']) || $selector['selector'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Selector Object requires a non-empty string "selector"');
    }
    if (isset($selector['type'])) {
        if (is_string($selector['type'])) {
            if (!in_array($selector['type'], ['jsonpath', 'xpath', 'jsonpointer'], true)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Selector Object "type" string must be jsonpath, xpath, or jsonpointer');
            }
        } else {
            validateExpressionTypeObject($selector['type']);
        }
    }
}

/**
 * Validates an Arazzo Criterion Object (`condition`, `context`, `type`).
 *
 * @param mixed $criterion The Criterion Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateCriterionObject(mixed $criterion): void
{
    if (!is_array($criterion)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Criterion Object must be an array/object');
    }
    if (!isset($criterion['condition']) || !is_string($criterion['condition']) || $criterion['condition'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Criterion Object requires a non-empty string "condition"');
    }
    if (isset($criterion['context']) && !is_string($criterion['context'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Criterion Object "context" must be a string');
    }
    if (isset($criterion['type'])) {
        if (is_string($criterion['type'])) {
            if (!in_array($criterion['type'], ['simple', 'regex', 'jsonpath', 'xpath'], true)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Criterion Object "type" string must be simple, regex, jsonpath, or xpath');
            }
            if ($criterion['type'] !== 'simple' && (!isset($criterion['context']) || $criterion['context'] === '')) {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Criterion Object "context" is required when type is ' . $criterion['type']);
            }
        } else {
            validateExpressionTypeObject($criterion['type']);
            if (!isset($criterion['context']) || $criterion['context'] === '') {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Criterion Object "context" is required when using an Expression Type Object');
            }
        }
    }
}

/**
 * Validates an Arazzo Reusable Object (`reference` and optional `value`).
 *
 * @param array<string, mixed> $reusable The Reusable Object to validate.
 * @param string $expectedPrefix Expected `$components.<category>.` prefix.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateReusableObject(array $reusable, string $expectedPrefix): void
{
    if (!isset($reusable['reference']) || !is_string($reusable['reference']) || !str_starts_with($reusable['reference'], $expectedPrefix)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation("Reusable Object 'reference' must start with '$expectedPrefix'");
    }
    foreach (array_keys($reusable) as $k) {
        if ($k !== 'reference' && $k !== 'value' && !str_starts_with((string) $k, 'x-')) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Reusable Object cannot contain additional field '$k'");
        }
    }
}

/**
 * Dereferences a Reusable Object against the specification's `components` registry.
 *
 * @param array<string, mixed> $reusable The Reusable Object containing `reference` and optional `value`.
 * @param array<string, mixed> $components The root `components` object.
 * @param string $category Component category (`parameters`, `successActions`, `failureActions`, `inputs`).
 * @return array<string, mixed> The resolved component object with any override applied.
 * @throws \Cdd\Exceptions\CddException If the referenced component does not exist.
 */
function resolveReusableObject(array $reusable, array $components, string $category): array
{
    $prefix = '$components.' . $category . '.';
    validateReusableObject($reusable, $prefix);
    $key = substr((string) $reusable['reference'], strlen($prefix));
    if (!isset($components[$category][$key]) || !is_array($components[$category][$key])) {
        throw \Cdd\Exceptions\CddException::arazzoRuntime("Referenced component not found: {$reusable['reference']}");
    }
    $resolved = $components[$category][$key];
    if ($category === 'parameters' && array_key_exists('value', $reusable)) {
        $resolved['value'] = $reusable['value'];
    }
    return $resolved;
}

/**
 * Validates an Arazzo Parameter Object or Reusable Parameter Object.
 *
 * @param mixed $param The Parameter or Reusable Object to validate.
 * @param bool $requireIn Whether the `in` field is required (true for operation steps, false for workflow steps).
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateArazzoParameterObject(mixed $param, bool $requireIn = false): void
{
    if (!is_array($param)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Parameter Object must be an array/object');
    }
    if (isset($param['reference'])) {
        validateReusableObject($param, '$components.parameters.');
        return;
    }
    if (!isset($param['name']) || !is_string($param['name']) || $param['name'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Parameter Object requires a non-empty string "name"');
    }
    if (!array_key_exists('value', $param)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Parameter Object requires a "value" field');
    }
    if (is_array($param['value']) && isset($param['value']['context'], $param['value']['selector'])) {
        validateSelectorObject($param['value']);
    }
    if (isset($param['in'])) {
        if (!is_string($param['in']) || !in_array($param['in'], ['path', 'query', 'querystring', 'header', 'cookie'], true)) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Parameter Object "in" must be one of: path, query, querystring, header, cookie');
        }
    } elseif ($requireIn) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Parameter Object "in" is required for operation step parameters');
    }
}

/**
 * Validates an Arazzo Payload Replacement Object.
 *
 * @param mixed $replacement The Payload Replacement Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validatePayloadReplacementObject(mixed $replacement): void
{
    if (!is_array($replacement)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Payload Replacement Object must be an array/object');
    }
    if (!isset($replacement['target']) || !is_string($replacement['target']) || $replacement['target'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Payload Replacement Object requires a non-empty string "target"');
    }
    if (!array_key_exists('value', $replacement)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Payload Replacement Object requires a "value" field');
    }
    if (isset($replacement['targetSelectorType'])) {
        if (is_string($replacement['targetSelectorType'])) {
            if (!in_array($replacement['targetSelectorType'], ['jsonpointer', 'jsonpath', 'xpath'], true)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Payload Replacement "targetSelectorType" must be jsonpointer, jsonpath, or xpath');
            }
        } else {
            validateExpressionTypeObject($replacement['targetSelectorType']);
        }
    }
}

/**
 * Validates an Arazzo Request Body Object.
 *
 * @param mixed $requestBody The Request Body Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateArazzoRequestBodyObject(mixed $requestBody): void
{
    if (!is_array($requestBody)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step "requestBody" must be an object');
    }
    if (isset($requestBody['contentType']) && !is_string($requestBody['contentType'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Request Body "contentType" must be a string');
    }
    if (isset($requestBody['replacements'])) {
        if (!is_array($requestBody['replacements'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Request Body "replacements" must be a list');
        }
        foreach ($requestBody['replacements'] as $rep) {
            validatePayloadReplacementObject($rep);
        }
    }
}

/**
 * Validates an Arazzo Success Action Object or Reusable Success Action Object.
 *
 * @param mixed $action The Success Action Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateSuccessActionObject(mixed $action): void
{
    if (!is_array($action)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action Object must be an array/object');
    }
    if (isset($action['reference'])) {
        validateReusableObject($action, '$components.successActions.');
        return;
    }
    if (!isset($action['name']) || !is_string($action['name']) || $action['name'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action Object requires a non-empty string "name"');
    }
    if (!isset($action['type']) || !is_string($action['type']) || !in_array($action['type'], ['end', 'goto'], true)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action Object "type" must be "end" or "goto"');
    }
    $hasWorkflow = isset($action['workflowId']);
    $hasStep = isset($action['stepId']);
    if ($action['type'] === 'end' && ($hasWorkflow || $hasStep)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action of type "end" cannot specify workflowId or stepId');
    }
    if ($action['type'] === 'goto') {
        if (($hasWorkflow && $hasStep) || (!$hasWorkflow && !$hasStep)) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action of type "goto" must specify exactly one of workflowId or stepId');
        }
    }
    if ($hasWorkflow && !is_string($action['workflowId'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action "workflowId" must be a string');
    }
    if ($hasStep && !is_string($action['stepId'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action "stepId" must be a string');
    }
    if (isset($action['parameters'])) {
        if (!is_array($action['parameters'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action "parameters" must be a list');
        }
        foreach ($action['parameters'] as $p) {
            validateArazzoParameterObject($p, false);
        }
    }
    if (isset($action['criteria'])) {
        if (!is_array($action['criteria'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Success Action "criteria" must be a list');
        }
        foreach ($action['criteria'] as $c) {
            validateCriterionObject($c);
        }
    }
}

/**
 * Validates an Arazzo Failure Action Object or Reusable Failure Action Object.
 *
 * @param mixed $action The Failure Action Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateFailureActionObject(mixed $action): void
{
    if (!is_array($action)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action Object must be an array/object');
    }
    if (isset($action['reference'])) {
        validateReusableObject($action, '$components.failureActions.');
        return;
    }
    if (!isset($action['name']) || !is_string($action['name']) || $action['name'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action Object requires a non-empty string "name"');
    }
    if (!isset($action['type']) || !is_string($action['type']) || !in_array($action['type'], ['end', 'retry', 'goto'], true)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action Object "type" must be "end", "retry", or "goto"');
    }
    $hasWorkflow = isset($action['workflowId']);
    $hasStep = isset($action['stepId']);
    if ($action['type'] === 'end' && ($hasWorkflow || $hasStep)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action of type "end" cannot specify workflowId or stepId');
    }
    if ($action['type'] === 'goto' && (($hasWorkflow && $hasStep) || (!$hasWorkflow && !$hasStep))) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action of type "goto" must specify exactly one of workflowId or stepId');
    }
    if ($action['type'] === 'retry' && $hasWorkflow && $hasStep) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action of type "retry" cannot specify both workflowId and stepId');
    }
    if ($hasWorkflow && !is_string($action['workflowId'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action "workflowId" must be a string');
    }
    if ($hasStep && !is_string($action['stepId'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action "stepId" must be a string');
    }
    if (isset($action['retryAfter']) && ((!is_int($action['retryAfter']) && !is_float($action['retryAfter'])) || $action['retryAfter'] < 0)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action "retryAfter" must be a non-negative number');
    }
    if (isset($action['retryLimit']) && (!is_int($action['retryLimit']) || $action['retryLimit'] < 0)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action "retryLimit" must be a non-negative integer');
    }
    if (isset($action['parameters'])) {
        if (!is_array($action['parameters'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action "parameters" must be a list');
        }
        foreach ($action['parameters'] as $p) {
            validateArazzoParameterObject($p, false);
        }
    }
    if (isset($action['criteria'])) {
        if (!is_array($action['criteria'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Failure Action "criteria" must be a list');
        }
        foreach ($action['criteria'] as $c) {
            validateCriterionObject($c);
        }
    }
}

/**
 * Validates an Arazzo Outputs map (used on both Workflow and Step objects).
 *
 * @param mixed $outputs The outputs map to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateOutputsMap(mixed $outputs): void
{
    if (!is_array($outputs)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "outputs" must be a map');
    }
    foreach ($outputs as $key => $val) {
        if (!is_string($key) || !preg_match('/^[a-zA-Z0-9\.\-_]+$/', $key)) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Invalid output key '$key'");
        }
        if (is_array($val)) {
            validateSelectorObject($val);
        } elseif (!is_string($val)) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Output '$key' must be a runtime expression string or Selector Object");
        }
    }
}

/**
 * Validates a single Arazzo Step Object.
 *
 * @param mixed $step The Step Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateStepObject(mixed $step): void
{
    if (!is_array($step)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step must be an object');
    }
    if (!isset($step['stepId']) || !is_string($step['stepId']) || $step['stepId'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step requires a non-empty string "stepId"');
    }
    $targets = [];
    foreach (['operationId', 'operationPath', 'channelPath', 'workflowId'] as $targetField) {
        if (isset($step[$targetField])) {
            if (!is_string($step[$targetField]) || $step[$targetField] === '') {
                throw \Cdd\Exceptions\CddException::arazzoValidation("Step '$targetField' must be a non-empty string");
            }
            $targets[] = $targetField;
        }
    }
    if (count($targets) !== 1) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step must specifyird exactly one of: operationId, operationPath, channelPath, or workflowId');
    }
    if (isset($step['description']) && !is_string($step['description'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step "description" must be a string');
    }
    $isSubWorkflow = isset($step['workflowId']);
    if (isset($step['parameters'])) {
        if (!is_array($step['parameters'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step "parameters" must be a list');
        }
        foreach ($step['parameters'] as $param) {
            validateArazzoParameterObject($param, !$isSubWorkflow);
        }
    }
    if (isset($step['requestBody'])) {
        if ($isSubWorkflow) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step targeting "workflowId" cannot contain "requestBody"');
        }
        validateArazzoRequestBodyObject($step['requestBody']);
    }
    if (isset($step['successCriteria'])) {
        if (!is_array($step['successCriteria'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step "successCriteria" must be a list');
        }
        foreach ($step['successCriteria'] as $criterion) {
            validateCriterionObject($criterion);
        }
    }
    if (isset($step['onSuccess'])) {
        if (!is_array($step['onSuccess'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step "onSuccess" must be a list');
        }
        foreach ($step['onSuccess'] as $action) {
            validateSuccessActionObject($action);
        }
    }
    if (isset($step['onFailure'])) {
        if (!is_array($step['onFailure'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step "onFailure" must be a list');
        }
        foreach ($step['onFailure'] as $action) {
            validateFailureActionObject($action);
        }
    }
    if (isset($step['outputs'])) {
        validateOutputsMap($step['outputs']);
    }
    if (isset($step['timeout']) && (!is_int($step['timeout']) || $step['timeout'] < 0)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step "timeout" must be a non-negative integer (milliseconds)');
    }
    if (isset($step['correlationId']) && !is_string($step['correlationId'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Step "correlationId" must be a string');
    }
    if (isset($step['action'])) {
        if (!is_string($step['action']) || !in_array($step['action'], ['send', 'receive'], true)) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step "action" must be "send" or "receive"');
        }
    }
    if (isset($step['dependsOn'])) {
        if (!is_array($step['dependsOn'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Step "dependsOn" must be a list of stepId strings');
        }
        foreach ($step['dependsOn'] as $dep) {
            if (!is_string($dep) || $dep === '') {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Each item in step "dependsOn" must be a non-empty string');
            }
        }
    }
}

/**
 * Validates that a directed dependency graph (`id => [prerequisiteIds]`) has no cycles.
 *
 * @param array<string, array<int, string>> $edges Map of node ID to list of prerequisite node IDs.
 * @param string $label Context label for error messages ('step' or 'workflow').
 * @return void
 * @throws \Cdd\Exceptions\CddException If a cycle or unknown local dependency is detected.
 */
function validateDagAcyclic(array $edges, string $label): void
{
    $state = []; // 0 = unvisited, 1 = visiting, 2 = visited
    $visit = static function (string $node) use (&$visit, &$state, $edges, $label): void {
        $st = $state[$node] ?? 0;
        if ($st === 1) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Cyclic $label dependency detected involving '$node'");
        }
        if ($st === 2) {
            return;
        }
        $state[$node] = 1;
        foreach ($edges[$node] ?? [] as $dep) {
            if (!str_starts_with($dep, '$sourceDescriptions.') && !array_key_exists($dep, $edges)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation("Unknown $label dependency '$dep' referenced by '$node'");
            }
            if (array_key_exists($dep, $edges)) {
                $visit($dep);
            }
        }
        $state[$node] = 2;
    };
    foreach (array_keys($edges) as $node) {
        $visit((string) $node);
    }
}

/**
 * Validates a single Arazzo Workflow Object.
 *
 * @param mixed $workflow The Workflow Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateWorkflowObject(mixed $workflow): void
{
    if (!is_array($workflow)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow must be an object');
    }
    if (!isset($workflow['workflowId']) || !is_string($workflow['workflowId']) || $workflow['workflowId'] === '') {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow requires a non-empty string "workflowId"');
    }
    if (isset($workflow['summary']) && !is_string($workflow['summary'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "summary" must be a string');
    }
    if (isset($workflow['description']) && !is_string($workflow['description'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "description" must be a string');
    }
    if (isset($workflow['inputs']) && !is_array($workflow['inputs'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "inputs" must be a JSON Schema object');
    }
    if (isset($workflow['dependsOn'])) {
        if (!is_array($workflow['dependsOn'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "dependsOn" must be a list of strings');
        }
        foreach ($workflow['dependsOn'] as $dep) {
            if (!is_string($dep) || $dep === '') {
                throw \Cdd\Exceptions\CddException::arazzoValidation('Each item in workflow "dependsOn" must be a non-empty string');
            }
        }
    }
    if (!isset($workflow['steps']) || !is_array($workflow['steps']) || empty($workflow['steps'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow requires a non-empty list of "steps"');
    }
    $stepDeps = [];
    foreach ($workflow['steps'] as $step) {
        validateStepObject($step);
        $stepId = (string) $step['stepId'];
        if (isset($stepDeps[$stepId])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Duplicate stepId '$stepId' in workflow '{$workflow['workflowId']}'");
        }
        $stepDeps[$stepId] = isset($step['dependsOn']) && is_array($step['dependsOn']) ? $step['dependsOn'] : [];
    }
    validateDagAcyclic($stepDeps, 'step');

    if (isset($workflow['successActions'])) {
        if (!is_array($workflow['successActions'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "successActions" must be a list');
        }
        foreach ($workflow['successActions'] as $action) {
            validateSuccessActionObject($action);
        }
    }
    if (isset($workflow['failureActions'])) {
        if (!is_array($workflow['failureActions'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "failureActions" must be a list');
        }
        foreach ($workflow['failureActions'] as $action) {
            validateFailureActionObject($action);
        }
    }
    if (isset($workflow['outputs'])) {
        validateOutputsMap($workflow['outputs']);
    }
    if (isset($workflow['parameters'])) {
        if (!is_array($workflow['parameters'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Workflow "parameters" must be a list');
        }
        foreach ($workflow['parameters'] as $param) {
            validateArazzoParameterObject($param, false);
        }
    }
}

/**
 * Validates the root `workflows` list and checks workflow-level `dependsOn` DAG.
 *
 * @param mixed $workflows The list of Workflow Objects.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateWorkflows(mixed $workflows): void
{
    if (!is_array($workflows) || empty($workflows)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "workflows" is REQUIRED and must be a non-empty list');
    }
    $workflowDeps = [];
    foreach ($workflows as $workflow) {
        validateWorkflowObject($workflow);
        $wfId = (string) $workflow['workflowId'];
        if (isset($workflowDeps[$wfId])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation("Duplicate workflowId: $wfId");
        }
        $workflowDeps[$wfId] = isset($workflow['dependsOn']) && is_array($workflow['dependsOn']) ? $workflow['dependsOn'] : [];
    }
    validateDagAcyclic($workflowDeps, 'workflow');
}

/**
 * Validates an Arazzo Components Object (`inputs`, `parameters`, `successActions`, `failureActions`).
 *
 * @param mixed $components The Components Object to validate.
 * @return void
 * @throws \Cdd\Exceptions\CddException If validation fails.
 */
function validateArazzoComponentsObject(mixed $components): void
{
    if (!is_array($components)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "components" must be an object');
    }
    $keyPattern = '/^[a-zA-Z0-9\.\-_]+$/';
    if (isset($components['inputs'])) {
        if (!is_array($components['inputs'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Components "inputs" must be a map');
        }
        foreach ($components['inputs'] as $k => $schema) {
            if (!is_string($k) || !preg_match($keyPattern, $k) || !is_array($schema)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation("Invalid component input '$k'");
            }
        }
    }
    if (isset($components['parameters'])) {
        if (!is_array($components['parameters'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Components "parameters" must be a map');
        }
        foreach ($components['parameters'] as $k => $param) {
            if (!is_string($k) || !preg_match($keyPattern, $k)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation("Invalid component parameter key '$k'");
            }
            validateArazzoParameterObject($param, false);
        }
    }
    if (isset($components['successActions'])) {
        if (!is_array($components['successActions'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Components "successActions" must be a map');
        }
        foreach ($components['successActions'] as $k => $action) {
            if (!is_string($k) || !preg_match($keyPattern, $k)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation("Invalid component successAction key '$k'");
            }
            validateSuccessActionObject($action);
        }
    }
    if (isset($components['failureActions'])) {
        if (!is_array($components['failureActions'])) {
            throw \Cdd\Exceptions\CddException::arazzoValidation('Components "failureActions" must be a map');
        }
        foreach ($components['failureActions'] as $k => $action) {
            if (!is_string($k) || !preg_match($keyPattern, $k)) {
                throw \Cdd\Exceptions\CddException::arazzoValidation("Invalid component failureAction key '$k'");
            }
            validateFailureActionObject($action);
        }
    }
}

/**
 * Parses and validates an Arazzo JSON document into a normalized Arazzo 1.1.0 array structure.
 *
 * @param string $json The raw Arazzo JSON string.
 * @return array<string, mixed> The validated Arazzo 1.1.0 specification array.
 * @throws \Cdd\Exceptions\CddException If JSON decoding or Arazzo validation fails.
 */
function parse(string $json): array
{
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw \Cdd\Exceptions\CddException::jsonDecode('Invalid JSON provided: ' . json_last_error_msg());
    }
    if (!is_array($data)) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Arazzo document must be a JSON object');
    }
    if (!isset($data['arazzo']) || !is_string($data['arazzo'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Missing REQUIRED field "arazzo" in Arazzo Specification Object');
    }
    if (!preg_match('/^1\.(0|1)\.[0-9]+$/', $data['arazzo'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "arazzo" version must be 1.0.x or 1.1.x');
    }
    $data['arazzo'] = '1.1.0';

    if (isset($data['$self']) && (!is_string($data['$self']) || $data['$self'] === '')) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Field "$self" must be a non-empty URI string');
    }
    if (!isset($data['info'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Missing REQUIRED field "info" in Arazzo Specification Object');
    }
    validateArazzoInfoObject($data['info']);

    if (!isset($data['sourceDescriptions'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Missing REQUIRED field "sourceDescriptions" in Arazzo Specification Object');
    }
    validateSourceDescriptions($data['sourceDescriptions']);

    if (!isset($data['workflows'])) {
        throw \Cdd\Exceptions\CddException::arazzoValidation('Missing REQUIRED field "workflows" in Arazzo Specification Object');
    }
    validateWorkflows($data['workflows']);

    if (isset($data['components'])) {
        validateArazzoComponentsObject($data['components']);
    }

    return $data;
}
