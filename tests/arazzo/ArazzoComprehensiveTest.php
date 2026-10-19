<?php

/**
 * Exhaustive validation and roundtrip test suite for Arazzo 1.0.x & 1.1.0 <-> PHP (SDK, CLI, Server).
 *
 * @package Cdd\Tests\Arazzo
 */

declare(strict_types=1);

namespace Cdd\Tests\Arazzo;

use Cdd\Tests\Framework\TestCase;

/**
 * Tests all Arazzo parsers, validators, runtime evaluators, emitters, and CLI commands.
 */
class ArazzoComprehensiveTest extends TestCase
{
    /**
     * Builds a comprehensive valid Arazzo 1.1.0 specification fixture.
     *
     * @return array<string, mixed> Valid Arazzo 1.1.0 array.
     */
    private function getValidSpec(): array
    {
        return [
            'arazzo' => '1.1.0',
            '$self' => 'https://example.com/workflows/petstore.arazzo.json',
            'x-root-ext' => 'custom',
            'info' => [
                'title' => 'Petstore Workflows',
                'summary' => 'End-to-end pet purchase workflows',
                'description' => 'Detailed workflow suite',
                'version' => '1.2.0',
                'x-info-ext' => true,
            ],
            'sourceDescriptions' => [
                [
                    'name' => 'petstore',
                    'url' => './openapi.json',
                    'type' => 'openapi',
                    'x-source-ext' => 'v1',
                ],
                [
                    'name' => 'events',
                    'url' => 'https://example.com/asyncapi.json',
                    'type' => 'asyncapi',
                ],
            ],
            'workflows' => [
                [
                    'workflowId' => 'loginWorkflow',
                    'summary' => 'User login flow',
                    'description' => 'Authenticates user and returns token',
                    'inputs' => [
                        'type' => 'object',
                        'properties' => [
                            'username' => ['type' => 'string'],
                            'count' => ['type' => 'integer'],
                            'ratio' => ['type' => 'number'],
                            'active' => ['type' => 'boolean'],
                            'tags' => ['type' => 'array'],
                            'meta' => ['type' => 'object'],
                        ],
                    ],
                    'parameters' => [
                        [
                            'name' => 'X-Trace',
                            'in' => 'header',
                            'value' => 'trace-1',
                        ],
                    ],
                    'steps' => [
                        [
                            'stepId' => 'authStep',
                            'description' => 'Call login operation',
                            'operationId' => 'petstore.loginUser',
                            'timeout' => 5000,
                            'correlationId' => '$request.header.X-Trace',
                            'action' => 'send',
                            'parameters' => [
                                [
                                    'reference' => '$components.parameters.sharedQuery',
                                    'value' => '$inputs.username',
                                ],
                                [
                                    'name' => 'petId',
                                    'in' => 'path',
                                    'value' => [
                                        'context' => '$inputs',
                                        'selector' => '$.username',
                                        'type' => 'jsonpath',
                                    ],
                                ],
                                [
                                    'name' => 'rawQuery',
                                    'in' => 'querystring',
                                    'value' => 'a=1&b=2',
                                ],
                            ],
                            'requestBody' => [
                                'contentType' => 'application/json',
                                'payload' => [
                                    'user' => '{$inputs.username}',
                                    'nested' => ['token' => 'initial'],
                                ],
                                'replacements' => [
                                    [
                                        'target' => '/nested/token',
                                        'value' => '$inputs.username',
                                        'targetSelectorType' => 'jsonpointer',
                                    ],
                                    [
                                        'target' => '$.nested.extra',
                                        'value' => 'added',
                                        'targetSelectorType' => [
                                            'type' => 'jsonpath',
                                            'version' => 'rfc9535',
                                        ],
                                    ],
                                ],
                            ],
                            'successCriteria' => [
                                [
                                    'condition' => '$statusCode == 200',
                                    'type' => 'simple',
                                ],
                                [
                                    'context' => '$response.body',
                                    'condition' => '$.token',
                                    'type' => [
                                        'type' => 'jsonpath',
                                        'version' => 'rfc9535',
                                    ],
                                ],
                            ],
                            'onSuccess' => [
                                [
                                    'reference' => '$components.successActions.logDone',
                                ],
                            ],
                            'onFailure' => [
                                [
                                    'reference' => '$components.failureActions.retryOnce',
                                ],
                            ],
                            'outputs' => [
                                'token' => '$response.body#/token',
                                'selectedToken' => [
                                    'context' => '$response.body',
                                    'selector' => '/token',
                                    'type' => [
                                        'type' => 'jsonpointer',
                                        'version' => 'rfc6901',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'successActions' => [
                        [
                            'name' => 'finishWf',
                            'type' => 'end',
                        ],
                    ],
                    'failureActions' => [
                        [
                            'name' => 'abortWf',
                            'type' => 'end',
                        ],
                    ],
                    'outputs' => [
                        'jwt' => '$steps.authStep.outputs.token',
                    ],
                ],
                [
                    'workflowId' => 'purchaseWorkflow',
                    'dependsOn' => ['loginWorkflow', '$sourceDescriptions.petstore.workflows.ext'],
                    'steps' => [
                        [
                            'stepId' => 'callLoginSub',
                            'workflowId' => 'loginWorkflow',
                            'parameters' => [
                                [
                                    'name' => 'username',
                                    'value' => 'alice',
                                ],
                            ],
                        ],
                        [
                            'stepId' => 'orderStep',
                            'dependsOn' => ['callLoginSub'],
                            'channelPath' => '/orders/created',
                            'action' => 'receive',
                        ],
                    ],
                ],
            ],
            'components' => [
                'inputs' => [
                    'UserCredentials' => [
                        'type' => 'object',
                        'properties' => [
                            'username' => ['type' => 'string'],
                        ],
                    ],
                ],
                'parameters' => [
                    'sharedQuery' => [
                        'name' => 'user',
                        'in' => 'query',
                        'value' => 'default_user',
                    ],
                ],
                'successActions' => [
                    'logDone' => [
                        'name' => 'logDone',
                        'type' => 'end',
                        'criteria' => [
                            ['condition' => '$statusCode == 200'],
                        ],
                    ],
                ],
                'failureActions' => [
                    'retryOnce' => [
                        'name' => 'retryOnce',
                        'type' => 'retry',
                        'retryAfter' => 0.5,
                        'retryLimit' => 2,
                        'criteria' => [
                            ['condition' => '$statusCode == 500'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests valid Arazzo parsing and all validation error branches in `src/arazzo/parse.php`.
     *
     * @return void
     */
    public function testParseAndValidateAllBranches(): void
    {
        $valid = $this->getValidSpec();
        $parsed = \Cdd\Arazzo\parse((string) json_encode($valid));
        $this->assertEquals('1.1.0', $parsed['arazzo']);
        $this->assertEquals(['x-root-ext' => 'custom'], \Cdd\Arazzo\extractExtensions($parsed));

        // 1.0.1 normalization
        $v10 = $valid;
        $v10['arazzo'] = '1.0.1';
        $parsed10 = \Cdd\Arazzo\parse((string) json_encode($v10));
        $this->assertEquals('1.1.0', $parsed10['arazzo']);

        // Root errors
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{bad json'), \RuntimeException::class, 'Invalid JSON');
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('"string"'), \RuntimeException::class, 'must be a JSON object');
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{}'), \RuntimeException::class, 'Missing REQUIRED field "arazzo"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{"arazzo":"2.0.0"}'), \RuntimeException::class, 'version must be 1.0.x or 1.1.x');
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{"arazzo":"1.1.0","$self":""}'), \RuntimeException::class, 'Field "$self" must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{"arazzo":"1.1.0"}'), \RuntimeException::class, 'Missing REQUIRED field "info"');

        // Info errors
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoInfoObject('bad'), \RuntimeException::class, '"info" must be an object');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoInfoObject([]), \RuntimeException::class, '"title"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoInfoObject(['title' => 'T']), \RuntimeException::class, '"version"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoInfoObject(['title' => 'T', 'version' => '1', 'summary' => 123]), \RuntimeException::class, '"summary"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoInfoObject(['title' => 'T', 'version' => '1', 'description' => 123]), \RuntimeException::class, '"description"');

        // SourceDescriptions errors
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{"arazzo":"1.1.0","info":{"title":"T","version":"1"}}'), \RuntimeException::class, 'Missing REQUIRED field "sourceDescriptions"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSourceDescriptions([]), \RuntimeException::class, 'non-empty list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSourceDescriptionObject('bad'), \RuntimeException::class, 'must be an object');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSourceDescriptionObject(['name' => 'bad name!']), \RuntimeException::class, '"name" is REQUIRED');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSourceDescriptionObject(['name' => 'ok']), \RuntimeException::class, '"url" is REQUIRED');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSourceDescriptionObject(['name' => 'ok', 'url' => '/a', 'type' => 'soap']), \RuntimeException::class, '"type" must be one of');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSourceDescriptions([['name' => 'dup', 'url' => '/a'], ['name' => 'dup', 'url' => '/b']]), \RuntimeException::class, 'Duplicate sourceDescription');

        // Workflows missing
        $this->assertThrows(static fn () => \Cdd\Arazzo\parse('{"arazzo":"1.1.0","info":{"title":"T","version":"1"},"sourceDescriptions":[{"name":"s","url":"/s"}]}'), \RuntimeException::class, 'Missing REQUIRED field "workflows"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflows([]), \RuntimeException::class, 'non-empty list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflows([['workflowId' => 'w1', 'steps' => [['stepId' => 's1', 'operationId' => 'op']]], ['workflowId' => 'w1', 'steps' => [['stepId' => 's1', 'operationId' => 'op']]]]), \RuntimeException::class, 'Duplicate workflowId');
    }

    /**
     * Tests validation branches for Workflow, Step, Parameter, Action, Criterion, Selector, Replacement, and Components.
     *
     * @return void
     */
    public function testValidateElementsAndDagBranches(): void
    {
        // Expression Type & Selector & Criterion
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateExpressionTypeObject('bad'), \RuntimeException::class, 'Expression Type Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateExpressionTypeObject(['type' => 'bad']), \RuntimeException::class, 'must be one of');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateExpressionTypeObject(['type' => 'jsonpath', 'version' => 'bad']), \RuntimeException::class, 'Invalid version');

        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSelectorObject('bad'), \RuntimeException::class, 'Selector Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSelectorObject([]), \RuntimeException::class, '"context"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSelectorObject(['context' => '$response.body']), \RuntimeException::class, '"selector"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSelectorObject(['context' => '$response.body', 'selector' => '$.a', 'type' => 'bad']), \RuntimeException::class, 'Selector Object "type" string');
        \Cdd\Arazzo\validateSelectorObject(['context' => '$response.body', 'selector' => '$.a', 'type' => ['type' => 'jsonpath', 'version' => 'rfc9535']]);

        $this->assertThrows(static fn () => \Cdd\Arazzo\validateCriterionObject('bad'), \RuntimeException::class, 'Criterion Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateCriterionObject([]), \RuntimeException::class, '"condition"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateCriterionObject(['condition' => 'a', 'context' => 123]), \RuntimeException::class, '"context" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateCriterionObject(['condition' => 'a', 'type' => 'bad']), \RuntimeException::class, 'Criterion Object "type" string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateCriterionObject(['condition' => 'a', 'type' => 'regex']), \RuntimeException::class, '"context" is required when type is regex');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateCriterionObject(['condition' => 'a', 'type' => ['type' => 'jsonpath', 'version' => 'rfc9535']]), \RuntimeException::class, '"context" is required when using an Expression Type Object');

        // Reusable Object
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateReusableObject(['reference' => 'bad'], '$components.parameters.'), \RuntimeException::class, 'must start with');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateReusableObject(['reference' => '$components.parameters.p1', 'extra' => 1], '$components.parameters.'), \RuntimeException::class, 'cannot contain additional field');
        $this->assertThrows(static fn () => \Cdd\Arazzo\resolveReusableObject(['reference' => '$components.parameters.missing'], [], 'parameters'), \RuntimeException::class, 'Referenced component not found');

        // Parameter Object
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoParameterObject('bad'), \RuntimeException::class, 'Parameter Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoParameterObject([]), \RuntimeException::class, '"name"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoParameterObject(['name' => 'p']), \RuntimeException::class, '"value"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoParameterObject(['name' => 'p', 'value' => 1, 'in' => 'bad']), \RuntimeException::class, '"in" must be one of');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoParameterObject(['name' => 'p', 'value' => 1], true), \RuntimeException::class, '"in" is required');

        // Payload Replacement & RequestBody
        $this->assertThrows(static fn () => \Cdd\Arazzo\validatePayloadReplacementObject('bad'), \RuntimeException::class, 'Payload Replacement Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validatePayloadReplacementObject([]), \RuntimeException::class, '"target"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validatePayloadReplacementObject(['target' => '/a']), \RuntimeException::class, '"value"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validatePayloadReplacementObject(['target' => '/a', 'value' => 1, 'targetSelectorType' => 'bad']), \RuntimeException::class, 'targetSelectorType');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoRequestBodyObject('bad'), \RuntimeException::class, 'Step "requestBody" must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoRequestBodyObject(['contentType' => 123]), \RuntimeException::class, '"contentType" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoRequestBodyObject(['replacements' => 'bad']), \RuntimeException::class, '"replacements" must be a list');

        // SuccessAction & FailureAction
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject('bad'), \RuntimeException::class, 'Success Action Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject([]), \RuntimeException::class, '"name"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'bad']), \RuntimeException::class, '"type" must be "end" or "goto"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'end', 'stepId' => 's1']), \RuntimeException::class, 'cannot specify workflowId or stepId');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'goto']), \RuntimeException::class, 'exactly one of workflowId or stepId');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'goto', 'workflowId' => 123]), \RuntimeException::class, '"workflowId" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'goto', 'stepId' => 123]), \RuntimeException::class, '"stepId" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'end', 'parameters' => 'bad']), \RuntimeException::class, '"parameters" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'end', 'criteria' => 'bad']), \RuntimeException::class, '"criteria" must be a list');
        \Cdd\Arazzo\validateSuccessActionObject(['name' => 'a', 'type' => 'goto', 'workflowId' => 'w', 'parameters' => [['name' => 'p', 'value' => 1]], 'criteria' => [['condition' => '$statusCode == 200']]]);

        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject('bad'), \RuntimeException::class, 'Failure Action Object must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject([]), \RuntimeException::class, '"name"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'bad']), \RuntimeException::class, '"type" must be');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'end', 'stepId' => 's']), \RuntimeException::class, 'cannot specify workflowId or stepId');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'goto']), \RuntimeException::class, 'exactly one of workflowId or stepId');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'workflowId' => 'w', 'stepId' => 's']), \RuntimeException::class, 'cannot specify both');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'workflowId' => 123]), \RuntimeException::class, '"workflowId" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'stepId' => 123]), \RuntimeException::class, '"stepId" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'retryAfter' => -1]), \RuntimeException::class, '"retryAfter"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'retryLimit' => -1]), \RuntimeException::class, '"retryLimit"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'parameters' => 'bad']), \RuntimeException::class, '"parameters" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'criteria' => 'bad']), \RuntimeException::class, '"criteria" must be a list');
        \Cdd\Arazzo\validateFailureActionObject(['name' => 'a', 'type' => 'retry', 'parameters' => [['name' => 'p', 'value' => 1]]]);

        // Outputs map
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateOutputsMap('bad'), \RuntimeException::class, '"outputs" must be a map');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateOutputsMap(['bad key!' => '$url']), \RuntimeException::class, 'Invalid output key');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateOutputsMap(['ok' => 123]), \RuntimeException::class, 'must be a runtime expression string');

        // Step validation errors
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject('bad'), \RuntimeException::class, 'Step must be an object');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject([]), \RuntimeException::class, '"stepId"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => '']), \RuntimeException::class, 'non-empty string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1']), \RuntimeException::class, 'exactly one of');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'description' => 1]), \RuntimeException::class, '"description" must be a string');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'parameters' => 'bad']), \RuntimeException::class, '"parameters" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'workflowId' => 'wf', 'requestBody' => []]), \RuntimeException::class, 'cannot contain "requestBody"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'successCriteria' => 'bad']), \RuntimeException::class, '"successCriteria" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'onSuccess' => 'bad']), \RuntimeException::class, '"onSuccess" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'onFailure' => 'bad']), \RuntimeException::class, '"onFailure" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'timeout' => -1]), \RuntimeException::class, '"timeout"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'correlationId' => 123]), \RuntimeException::class, '"correlationId"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'action' => 'bad']), \RuntimeException::class, '"action"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'dependsOn' => 'bad']), \RuntimeException::class, '"dependsOn" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateStepObject(['stepId' => 's1', 'operationId' => 'op', 'dependsOn' => ['']]), \RuntimeException::class, 'Each item in step "dependsOn"');

        // DAG cycle & unknown dependency
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateDagAcyclic(['a' => ['b'], 'b' => ['a']], 'step'), \RuntimeException::class, 'Cyclic step dependency');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateDagAcyclic(['a' => ['missing']], 'step'), \RuntimeException::class, 'Unknown step dependency');

        // Workflow validation errors
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject('bad'), \RuntimeException::class, 'Workflow must be an object');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject([]), \RuntimeException::class, '"workflowId"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'summary' => 1]), \RuntimeException::class, '"summary"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'description' => 1]), \RuntimeException::class, '"description"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'inputs' => 'bad']), \RuntimeException::class, '"inputs"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'dependsOn' => 'bad']), \RuntimeException::class, '"dependsOn" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'dependsOn' => ['']]), \RuntimeException::class, 'Each item in workflow "dependsOn"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'steps' => []]), \RuntimeException::class, 'non-empty list of "steps"');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'steps' => [['stepId' => 's', 'operationId' => 'o'], ['stepId' => 's', 'operationId' => 'o']]]), \RuntimeException::class, 'Duplicate stepId');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'steps' => [['stepId' => 's', 'operationId' => 'o']], 'successActions' => 'bad']), \RuntimeException::class, '"successActions" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'steps' => [['stepId' => 's', 'operationId' => 'o']], 'failureActions' => 'bad']), \RuntimeException::class, '"failureActions" must be a list');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateWorkflowObject(['workflowId' => 'w', 'steps' => [['stepId' => 's', 'operationId' => 'o']], 'parameters' => 'bad']), \RuntimeException::class, '"parameters" must be a list');

        // Components validation errors
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject('bad'), \RuntimeException::class, '"components" must be an object');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['inputs' => 'bad']), \RuntimeException::class, 'Components "inputs" must be a map');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['inputs' => ['bad key!' => []]]), \RuntimeException::class, 'Invalid component input');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['parameters' => 'bad']), \RuntimeException::class, 'Components "parameters" must be a map');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['parameters' => ['bad key!' => []]]), \RuntimeException::class, 'Invalid component parameter key');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['successActions' => 'bad']), \RuntimeException::class, 'Components "successActions" must be a map');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['successActions' => ['bad key!' => []]]), \RuntimeException::class, 'Invalid component successAction key');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['failureActions' => 'bad']), \RuntimeException::class, 'Components "failureActions" must be a map');
        $this->assertThrows(static fn () => \Cdd\Arazzo\validateArazzoComponentsObject(['failureActions' => ['bad key!' => []]]), \RuntimeException::class, 'Invalid component failureAction key');
    }

    /**
     * Tests `loadSourceDescriptions` across OpenAPI, Arazzo, and generic JSON files.
     *
     * @return void
     */
    public function testLoadSourceDescriptions(): void
    {
        $tmpDir = sys_get_temp_dir() . '/cdd_arazzo_src_' . uniqid();
        mkdir($tmpDir, 0777, true);
        try {
            file_put_contents("$tmpDir/openapi.json", json_encode([
                'openapi' => '3.2.0',
                'info' => ['title' => 'T', 'version' => '1.0'],
                'paths' => [],
            ]));
            file_put_contents("$tmpDir/sub.arazzo.json", json_encode($this->getValidSpec()));
            file_put_contents("$tmpDir/async.json", json_encode(['asyncapi' => '3.0.0']));

            $loaded = \Cdd\Arazzo\loadSourceDescriptions([
                ['name' => 'remote', 'url' => 'https://example.com/spec.json', 'type' => 'openapi'],
                ['name' => 'oas', 'url' => './openapi.json', 'type' => 'openapi'],
                ['name' => 'sub', 'url' => "$tmpDir/sub.arazzo.json", 'type' => 'arazzo'],
                ['name' => 'async', 'url' => 'async.json', 'type' => 'asyncapi'],
            ], $tmpDir);

            $this->assertArrayHasKey('oas', $loaded);
            $this->assertArrayHasKey('sub', $loaded);
            $this->assertArrayHasKey('async', $loaded);
        } finally {
            $this->removeDir($tmpDir);
        }
    }

    /**
     * Tests the runtime expression, JSON Pointer, JSONPath, XPath, Selector, Criterion, and Replacement evaluators.
     *
     * @return void
     */
    public function testRuntimeExpressionAndCriterionEngine(): void
    {
        $this->assertTrue(\Cdd\Arazzo\validateRuntimeExpression('$url'));
        $this->assertTrue(\Cdd\Arazzo\validateRuntimeExpression('$response.body#/id'));
        $this->assertFalse(\Cdd\Arazzo\validateRuntimeExpression('literal'));

        $data = ['items' => [['id' => 10, 'price' => 5], ['id' => 20, 'price' => 15]], 'a/b~c' => 'escaped'];
        $this->assertEquals($data, \Cdd\Arazzo\getJsonPointer($data, ''));
        $this->assertEquals(10, \Cdd\Arazzo\getJsonPointer($data, '#/items/0/id'));
        $this->assertEquals('escaped', \Cdd\Arazzo\getJsonPointer($data, '/a~1b~0c'));
        $this->assertNull(\Cdd\Arazzo\getJsonPointer($data, '/items/99/id'));
        $this->assertNull(\Cdd\Arazzo\getJsonPointer('scalar', '/a'));

        \Cdd\Arazzo\setJsonPointer($data, '/items/0/id', 99);
        \Cdd\Arazzo\setJsonPointer($data, '/newBranch/leaf', 'val');
        $this->assertEquals(99, $data['items'][0]['id']);
        $this->assertEquals('val', $data['newBranch']['leaf']);

        // JSONPath
        $this->assertEquals(99, \Cdd\Arazzo\queryJsonPath($data, '/items/0/id'));
        $this->assertEquals($data, \Cdd\Arazzo\queryJsonPath($data, '$'));
        $this->assertEquals([99, 20], \Cdd\Arazzo\queryJsonPath($data, '$..id'));
        $this->assertNull(\Cdd\Arazzo\queryJsonPath($data, '$..nonexistent'));
        $this->assertEquals(20, \Cdd\Arazzo\queryJsonPath($data, '$.items[1].id'));
        $this->assertCount(2, (array) \Cdd\Arazzo\queryJsonPath($data, '$.items[*]'));
        $this->assertNull(\Cdd\Arazzo\queryJsonPath($data, '$.items[99]'));
        $this->assertNull(\Cdd\Arazzo\queryJsonPath('scalar', '$.a'));

        \Cdd\Arazzo\replaceJsonPath($data, '$.items[1].id', 42);
        $this->assertEquals(42, $data['items'][1]['id']);

        // XPath
        $this->assertEquals(42, \Cdd\Arazzo\queryXPath($data, '/items/1/id'));
        $this->assertNull(\Cdd\Arazzo\queryXPath('', '//item'));
        $this->assertNull(\Cdd\Arazzo\queryXPath('<broken', '//item'));
        $this->assertEquals('hello', \Cdd\Arazzo\queryXPath('<root><item>hello</item></root>', '//item'));
        $this->assertNull(\Cdd\Arazzo\queryXPath('<root><item>hello</item></root>', '//missing'));
        $this->assertEquals(1.0, \Cdd\Arazzo\queryXPath('<root><item>hello</item></root>', 'count(//item)'));

        // Full context evaluation
        $ctx = [
            'url' => 'https://api.example.com/pets',
            'method' => 'POST',
            'statusCode' => 201,
            'self' => 'https://api.example.com/arazzo.json',
            'request' => [
                'headers' => ['X-Req-Id' => 'req-1'],
                'query' => ['filter' => 'dogs'],
                'path' => ['petId' => '7'],
                'body' => ['name' => 'Rex'],
            ],
            'response' => [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => ['id' => 101, 'status' => 'active', 'xml' => '<r><v>ok</v></r>'],
            ],
            'message' => [
                'headers' => ['X-Msg' => 'm1'],
                'payload' => ['event' => 'created'],
            ],
            'inputs' => ['user' => ['name' => 'alice']],
            'outputs' => ['final' => 'done'],
            'steps' => ['s1' => ['outputs' => ['tok' => 'secret'], ' body' => ['x' => 1]]],
            'workflows' => ['wf1' => ['outputs' => ['res' => 'ok']]],
            'sourceDescriptions' => ['petstore' => ['url' => 'https://petstore.example.com']],
            'components' => ['parameters' => ['p1' => ['value' => 'v1']]],
        ];

        $this->assertEquals('https://api.example.com/pets', \Cdd\Arazzo\evaluateRuntimeExpression('$url', $ctx));
        $this->assertEquals('POST', \Cdd\Arazzo\evaluateRuntimeExpression('$method', $ctx));
        $this->assertEquals(201, \Cdd\Arazzo\evaluateRuntimeExpression('$statusCode', $ctx));
        $this->assertEquals('https://api.example.com/arazzo.json', \Cdd\Arazzo\evaluateRuntimeExpression('$self', $ctx));
        $this->assertEquals('req-1', \Cdd\Arazzo\evaluateRuntimeExpression('$request.header.x-req-id', $ctx));
        $this->assertNull(\Cdd\Arazzo\evaluateRuntimeExpression('$request.header.missing', $ctx));
        $this->assertEquals('dogs', \Cdd\Arazzo\evaluateRuntimeExpression('$request.query.filter', $ctx));
        $this->assertEquals('7', \Cdd\Arazzo\evaluateRuntimeExpression('$request.path.petId', $ctx));
        $this->assertEquals('Rex', \Cdd\Arazzo\evaluateRuntimeExpression('$request.body#/name', $ctx));
        $this->assertEquals('application/json', \Cdd\Arazzo\evaluateRuntimeExpression('$response.header.content-type', $ctx));
        $this->assertEquals(101, \Cdd\Arazzo\evaluateRuntimeExpression('$response.body#/id', $ctx));
        $this->assertEquals(101, \Cdd\Arazzo\evaluateRuntimeExpression('$response.body.id', $ctx));
        $this->assertNull(\Cdd\Arazzo\evaluateRuntimeExpression('$response.body.missing', $ctx));
        $this->assertEquals('m1', \Cdd\Arazzo\evaluateRuntimeExpression('$message.header.x-msg', $ctx));
        $this->assertEquals('created', \Cdd\Arazzo\evaluateRuntimeExpression('$message.payload#/event', $ctx));
        $this->assertEquals('alice', \Cdd\Arazzo\evaluateRuntimeExpression('$inputs.user#/name', $ctx));
        $this->assertEquals('done', \Cdd\Arazzo\evaluateRuntimeExpression('$outputs.final', $ctx));
        $this->assertEquals('secret', \Cdd\Arazzo\evaluateRuntimeExpression('$steps.s1.outputs.tok', $ctx));
        $this->assertEquals('ok', \Cdd\Arazzo\evaluateRuntimeExpression('$workflows.wf1.outputs.res', $ctx));
        $this->assertEquals('https://petstore.example.com', \Cdd\Arazzo\evaluateRuntimeExpression('$sourceDescriptions.petstore.url', $ctx));
        $this->assertEquals('v1', \Cdd\Arazzo\evaluateRuntimeExpression('$components.parameters.p1.value', $ctx));
        $this->assertEquals('$unknown.token', \Cdd\Arazzo\evaluateRuntimeExpression('$unknown.token', $ctx));
        $this->assertEquals('Bearer secret', \Cdd\Arazzo\evaluateRuntimeExpression('Bearer {$steps.s1.outputs.tok}', $ctx));
        $this->assertEquals(123, \Cdd\Arazzo\evaluateRuntimeExpression(123, $ctx));
        $this->assertEquals(['a' => 'secret'], \Cdd\Arazzo\evaluateRuntimeExpression(['a' => '$steps.s1.outputs.tok'], $ctx));

        // Selector evaluation
        $this->assertEquals(101, \Cdd\Arazzo\evaluateSelector(['context' => '$response.body', 'selector' => '/id', 'type' => 'jsonpointer'], $ctx));
        $this->assertEquals('ok', \Cdd\Arazzo\evaluateSelector(['context' => '$response.body#/xml', 'selector' => '//v', 'type' => 'xpath'], $ctx));
        $this->assertEquals(101, \Cdd\Arazzo\evaluateRuntimeExpression(['context' => '$response.body', 'selector' => '$.id', 'type' => 'jsonpath'], $ctx));

        // Criterion evaluation
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['condition' => '$statusCode == 201 && $response.body#/status == "active"'], $ctx));
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['condition' => '$statusCode != 500 && $statusCode > 200 && $statusCode >= 201 && $statusCode < 300 && $statusCode <= 201'], $ctx));
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['condition' => '!false && true && null == null && 1.5 > 1 && plainToken'], $ctx));
        $this->assertFalse(\Cdd\Arazzo\evaluateCriterion(['condition' => '$statusCode == 404 || $statusCode == 500'], $ctx));
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['context' => '$response.body#/status', 'condition' => '^act', 'type' => 'regex'], $ctx));
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['context' => '$response.body', 'condition' => '$.id', 'type' => 'jsonpath'], $ctx));
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['context' => '$response.body', 'condition' => '/id', 'type' => 'jsonpointer'], $ctx));
        $this->assertTrue(\Cdd\Arazzo\evaluateCriterion(['context' => '$response.body#/xml', 'condition' => '//v', 'type' => 'xpath'], $ctx));

        // Payload replacements
        $this->assertEquals('scalar', \Cdd\Arazzo\applyPayloadReplacements('scalar', [], $ctx));
        $repOut = \Cdd\Arazzo\applyPayloadReplacements(['a' => 1, 'b' => 2], [
            ['target' => '/a', 'value' => '$statusCode', 'targetSelectorType' => 'jsonpointer'],
            ['target' => '$.b', 'value' => '$method', 'targetSelectorType' => 'jsonpath'],
        ], $ctx);
        $this->assertEquals(['a' => 201, 'b' => 'POST'], $repOut);
    }

    /**
     * Tests Client SDK, CLI, and Server emission, execution, and reverse AST parsing roundtrips.
     *
     * @return void
     */
    public function testClientSdkCliServerEmitExecuteAndRoundtrip(): void
    {
        $spec = $this->getValidSpec();

        // 1. Client SDK emit + parse roundtrip
        $this->assertEquals('workflow', \Cdd\ArazzoClient\toCamelCaseIdentifier('---'));
        $this->assertEquals('wf123Flow', \Cdd\ArazzoClient\toCamelCaseIdentifier('123-flow'));

        $dtoCode = \Cdd\ArazzoClient\emitInputDtos($spec);
        $this->assertStringContainsString('class LoginWorkflowInput', $dtoCode);
        $this->assertStringContainsString('class UserCredentialsInput', $dtoCode);

        $untypedDtoSpec = [
            'workflows' => [
                [
                    'workflowId' => 'untypedFlow',
                    'inputs' => [
                        'type' => 'object',
                        'properties' => ['raw' => 'non_array_schema_node'],
                    ],
                    'steps' => [],
                ],
            ],
        ];
        $this->assertStringContainsString('class UntypedFlowInput', \Cdd\ArazzoClient\emitInputDtos($untypedDtoSpec));
        $this->assertEquals(['raw' => 'sample'], \Cdd\ArazzoServer\generateSeedInputsFromSchema(['properties' => ['raw' => 'non_array_schema_node']]));

        $sdkCode = \Cdd\ArazzoClient\emit($spec);
        $this->assertStringContainsString('class ApiWorkflows', $sdkCode);
        $this->assertFalse(str_contains($sdkCode, '@arazzo-spec'));
        $parsedFromSdk = \Cdd\ArazzoClient\parse($sdkCode);
        $this->assertEquals($spec, $parsedFromSdk);

        // Execute generated ApiWorkflows class in-memory with custom mock transport covering goto, retry, end, dependsOn
        $uniqueClass = 'ApiWorkflows_' . uniqid();
        $evalCode = str_replace(['<?php', 'declare(strict_types=1);', 'namespace Api;', 'class ApiWorkflows'], ['', '', '', "class {$uniqueClass}"], $sdkCode);
        eval($evalCode);

        $attempts = 0;
        $transport = static function (string $op, array $params, mixed $body) use (&$attempts): array {
            $attempts++;
            if ($op === 'loginUser' && $attempts === 1) {
                return ['status' => 500, 'headers' => [], 'data' => ['error' => 'temp']];
            }
            return ['status' => 200, 'headers' => ['X-Rate' => '10'], 'data' => ['token' => 'jwt_xyz', 'echo' => $body]];
        };
        /** @var object $runner */
        $runner = new $uniqueClass($transport, ['petstore' => $transport]);
        $stepLog = [];
        $runner->onStep = static function (string $wfId, array $step) use (&$stepLog): void {
            $stepLog[] = $wfId . ':' . $step['stepId'];
        };

        $dryRes = $runner->executeWorkflow('loginWorkflow', ['username' => 'bob'], null, true);
        $this->assertTrue($dryRes['dryRun']);

        $out = $runner->purchaseWorkflow(['username' => 'bob']);
        $this->assertTrue(is_array($out));
        $this->assertEquals('jwt_xyz', $runner->workflowState['loginWorkflow']['outputs']['jwt']);
        $this->assertTrue(count($stepLog) >= 3);

        // Test fallback AST parsing when getSpecification() is absent
        $handwrittenSdk = <<<'PHP'
<?php
/**
 * @arazzo-version 1.1.0
 * @arazzo-self https://example.com/self.json
 * @title Hand SDK
 * @version 2.0.0
 * @summary Hand summary
 * @description Hand desc
 * @arazzo-source {"name":"api","url":"./api.json","type":"openapi"}
 * @arazzo-components {"parameters":{"p":{"name":"p","in":"query","value":"1"}}}
 */
class CustomWorkflows {
    /**
     * @arazzo-workflow-id customFlow
     */
    public function customFlow(): array {
        $this->client->firstOp();
        $this->executeWorkflow('subFlow');
        return [true, false, null, 1, 1.5, 'str', [1, 'k' => 'v'], $var];
    }
    /**
     * @arazzo-workflow {"workflowId":"annotatedFlow","steps":[{"stepId":"s1","operationId":"op1"}]}
     */
    public function annotatedFlow(): array {
        return [];
    }
    public function astVarFlow(): array {
        $workflow = ['workflowId' => 'varFlow', 'steps' => [['stepId' => 's1', 'operationId' => 'opVar']]];
        return $workflow;
    }
    private function helper(): void {}
}
PHP;
        $parsedHand = \Cdd\ArazzoClient\parse($handwrittenSdk);
        $this->assertEquals('Hand SDK', $parsedHand['info']['title']);
        $this->assertCount(3, $parsedHand['workflows']);
        $this->assertEquals([], \Cdd\ArazzoClient\parse('<?php invalid syntax {{{'));
        $this->assertEquals([], \Cdd\ArazzoClient\parse(''));
        $this->assertEquals([], \Cdd\ArazzoCli\parse('<?php invalid syntax {{{')['workflows']);
        $this->assertNull(\Cdd\ArazzoClient\astExprToPhpValue(new \PhpParser\Node\Expr\Variable('x')));
        $arrWithNull = new \PhpParser\Node\Expr\Array_([null, new \PhpParser\Node\Expr\ArrayItem(new \PhpParser\Node\Scalar\Int_(1))]);
        $this->assertEquals([1], \Cdd\ArazzoClient\astExprToPhpValue($arrWithNull));
        $this->assertTrue(\Cdd\ArazzoClient\astExprToPhpValue(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true'))));
        $this->assertFalse(\Cdd\ArazzoClient\astExprToPhpValue(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('false'))));
        $this->assertNull(\Cdd\ArazzoClient\astExprToPhpValue(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null'))));
        $this->assertEquals(1.5, \Cdd\ArazzoClient\astExprToPhpValue(new \PhpParser\Node\Scalar\Float_(1.5)));

        // 2. Client CLI emit + parse roundtrip
        $cliCode = \Cdd\ArazzoCli\emit($spec);
        $this->assertStringContainsString('arazzo_cli.php', $cliCode);
        $parsedFromCli = \Cdd\ArazzoCli\parse($cliCode);
        $this->assertEquals($spec, $parsedFromCli);

        $fallbackCli = "<?php
if (\$command === '--help') {}
if (\$command === 'myCliWf') {}
";
        $parsedFallbackCli = \Cdd\ArazzoCli\parse($fallbackCli);
        $this->assertEquals('myCliWf', $parsedFallbackCli['workflows'][0]['workflowId']);
        $this->assertEquals($spec, \Cdd\ArazzoCli\parse('<?php // empty', $sdkCode));

        // 3. Server Engine + E2E Test emit + parse roundtrip
        $engineCode = \Cdd\ArazzoServer\emitWorkflowEngine($spec);
        $this->assertStringContainsString('class WorkflowEngine', $engineCode);
        $serverTestCode = \Cdd\ArazzoServer\emit($spec);
        $this->assertStringContainsString('class ArazzoWorkflowTest', $serverTestCode);
        $this->assertEquals($spec, \Cdd\ArazzoServer\parse($serverTestCode));
        $this->assertEquals($spec, \Cdd\ArazzoServer\parse($engineCode));

        $handServerTest = <<<'PHP'
<?php
class RouteFlowTest {
    public function helper(): void {}
    public function testCheckoutFlow(): void {
        $this->call('POST', '/cart');
        $this->call('POST', '/checkout');
    }
}
PHP;
        $parsedServerTest = \Cdd\ArazzoServer\parse($handServerTest);
        $this->assertEquals('checkoutFlow', $parsedServerTest['workflows'][0]['workflowId']);
        $this->assertEquals([], \Cdd\ArazzoServer\parse('<?php invalid {{{'));
        $this->assertEquals([], \Cdd\ArazzoServer\parse(''));
        $this->assertEquals([], \Cdd\ArazzoServer\parse('<?php class NoTests {}'));
    }

    /**
     * Tests `ApiWorkflows` branching (`goto` step/workflow, failure `goto`/`end`, object client, step filter).
     *
     * @return void
     */
    public function testApiWorkflowsBranchingAndFailureModes(): void
    {
        $branchSpec = [
            'arazzo' => '1.1.0',
            'info' => ['title' => 'Branch Test', 'version' => '1.0.0'],
            'sourceDescriptions' => [['name' => 'default', 'url' => './openapi.json', 'type' => 'openapi']],
            'workflows' => [
                [
                    'workflowId' => 'flowA',
                    'steps' => [
                        [
                            'stepId' => 's1',
                            'dependsOn' => ['s2'],
                            'operationId' => 'opOne',
                            'onSuccess' => [
                                ['name' => 'skipCriteria', 'type' => 'end', 'criteria' => [['condition' => '$statusCode == 999']]],
                                ['name' => 'chainB', 'type' => 'goto', 'workflowId' => 'flowB'],
                            ],
                        ],
                        [
                            'stepId' => 's2',
                            'operationId' => 'opTwo',
                            'onSuccess' => [
                                ['name' => 'jumpToS1', 'type' => 'goto', 'stepId' => 's1'],
                            ],
                        ],
                    ],
                ],
                [
                    'workflowId' => 'flowB',
                    'steps' => [
                        [
                            'stepId' => 'b1',
                            'operationId' => 'opB',
                            'successCriteria' => [['condition' => '$statusCode == 200']],
                            'onFailure' => [
                                ['name' => 'skipFail', 'type' => 'end', 'criteria' => [['condition' => '$statusCode == 999']]],
                                ['name' => 'retryStep', 'type' => 'retry', 'stepId' => 'b1', 'retryLimit' => 1],
                                ['name' => 'gotoStep', 'type' => 'goto', 'stepId' => 'b2'],
                            ],
                        ],
                        [
                            'stepId' => 'b2',
                            'operationId' => 'opFallback',
                            'successCriteria' => [['condition' => '$statusCode == 200']],
                            'onFailure' => [
                                ['name' => 'gotoRecoveryWf', 'type' => 'goto', 'workflowId' => 'flowC'],
                            ],
                        ],
                    ],
                ],
                [
                    'workflowId' => 'flowC',
                    'steps' => [
                        [
                            'stepId' => 'c1',
                            'operationId' => 'opC',
                        ],
                    ],
                    'outputs' => ['recovered' => 'yes'],
                ],
                [
                    'workflowId' => 'flowFailEnd',
                    'steps' => [
                        [
                            'stepId' => 'fe1',
                            'operationId' => 'opFail',
                            'successCriteria' => [['condition' => '$statusCode == 200']],
                            'onFailure' => [
                                ['name' => 'hardEnd', 'type' => 'end'],
                            ],
                        ],
                    ],
                ],
                [
                    'workflowId' => 'flowUnhandledFail',
                    'steps' => [
                        [
                            'stepId' => 'uf1',
                            'operationId' => 'opFail',
                            'successCriteria' => [['condition' => '$statusCode == 200']],
                        ],
                    ],
                ],
            ],
        ];

        $sdkCode = \Cdd\ArazzoClient\emit($branchSpec);
        $cls = 'ApiWorkflowsBranch_' . uniqid();
        eval(str_replace(['<?php', 'declare(strict_types=1);', 'namespace Api;', 'class ApiWorkflows'], ['', '', '', "class {$cls}"], $sdkCode));

        // 1. Null client default dispatch + step filter + unknown workflow
        /** @var object $wNull */
        $wNull = new $cls(null);
        $this->assertThrows(static fn () => $wNull->executeWorkflow('missing'), \RuntimeException::class, 'Unknown workflowId');
        $filtered = $wNull->executeWorkflow('flowC', [], 'c1');
        $this->assertEquals(['recovered' => 'yes'], $filtered);
        $skippedAll = $wNull->executeWorkflow('flowC', [], 'nonexistent_step');
        $this->assertEquals(['recovered' => 'yes'], $skippedAll);

        // 2. Object client with method
        $objClient = new class () {
            /**
             * Mock operation C.
             *
             * @return array<string, mixed>
             */
            public function opC(): array
            {
                return ['status' => 200, 'data' => ['ok' => true]];
            }
        };
        /** @var object $wObj */
        $wObj = new $cls($objClient);
        $this->assertEquals(['recovered' => 'yes'], $wObj->flowC());

        // 3. Callable client returning raw non-status value vs status array
        /** @var object $wRaw */
        $wRaw = new $cls(static fn (): array => ['hello' => 'world']);
        $this->assertEquals(['recovered' => 'yes'], $wRaw->flowC());

        // 4. Full branching: s1 dependsOn s2 -> runs s2 -> jumps to s1 -> chains flowB -> b1 fails & retries -> jumps to b2 -> fails & chains flowC
        /** @var object $wBranch */
        $wBranch = new $cls(static function (string $op): array {
            if ($op === 'opB' || $op === 'opFallback' || $op === 'opFail') {
                return ['status' => 500, 'data' => null];
            }
            return ['status' => 200, 'data' => []];
        });
        $res = $wBranch->flowA();
        $this->assertEquals(['recovered' => 'yes'], $res);

        $this->assertThrows(static fn () => $wBranch->flowFailEnd(), \RuntimeException::class, 'terminated at step');
        $this->assertThrows(static fn () => $wBranch->flowUnhandledFail(), \RuntimeException::class, 'failed successCriteria');
    }

    /**
     * Tests `\Cdd\Arazzo\emit`, `downgradeArazzo11To10`, and `CddCli` (`from_arazzo`, `to_arazzo`, `sync`, MCP, help).
     *
     * @return void
     */
    public function testMasterEmitterAndCddCliCommands(): void
    {
        $spec = $this->getValidSpec();
        $downgraded = \Cdd\Arazzo\downgradeArazzo11To10($spec);
        $this->assertEquals('1.0.1', $downgraded['arazzo']);
        $this->assertFalse(isset($downgraded['$self']));

        $defaultEmitJson = \Cdd\Arazzo\emit([]);
        $this->assertStringContainsString('"1.1.0"', $defaultEmitJson);
        $v10Json = \Cdd\Arazzo\emit($spec, null, ['target_version' => '1.0.1']);
        $this->assertStringContainsString('"1.0.1"', $v10Json);

        $tmpDir = sys_get_temp_dir() . '/cdd_arazzo_cli_' . uniqid();
        mkdir($tmpDir, 0777, true);
        try {
            $specFile = "$tmpDir/arazzo.json";
            file_put_contents($specFile, json_encode($spec));

            // Help commands
            ob_start();
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'from_arazzo', '--help']));
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', '--help']));
            $helpOut = (string) ob_get_clean();
            $this->assertStringContainsString('Usage: cdd-php from_arazzo', $helpOut);
            $this->assertStringContainsString('Usage: cdd-php to_arazzo', $helpOut);

            // Programmatic helpers with array & CddConfig
            $outSdk = "$tmpDir/out_sdk";
            ob_start();
            $code1 = \Cdd\Cli\CddCli::generate_from_arazzo(['to_sdk_cli', '-i', $specFile, '-o', $outSdk, '--no-github-actions', '--no-installable-package']);
            ob_end_clean();
            $this->assertEquals(0, $code1);
            $this->assertTrue(file_exists("$outSdk/src/ApiWorkflows.php"));
            $this->assertTrue(file_exists("$outSdk/src/arazzo_cli.php"));

            $cfg = new \Cdd\Cli\CddConfig();
            $cfg->input = $specFile;
            $cfg->output = "$tmpDir/out_server";
            $cfg->target = 'to_server';
            ob_start();
            $code2 = \Cdd\Cli\CddCli::generate_from_arazzo($cfg);
            ob_end_clean();
            $this->assertEquals(0, $code2);
            $this->assertTrue(file_exists("$tmpDir/out_server/src/WorkflowEngine.php"));
            $this->assertTrue(file_exists("$tmpDir/out_server/tests/Workflows/ArazzoWorkflowTest.php"));

            // Input dir mode & missing file error
            ob_start();
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'from_arazzo', 'to_sdk', '-d', $tmpDir, '-o', "$tmpDir/out_dir_mode"]));
            $this->assertEquals(1, \Cdd\Cli\CddCli::run(['cdd-php', 'from_arazzo', '-i', "$tmpDir/missing.json"]));
            ob_end_clean();

            // to_arazzo across directory and individual file modes
            $extractedFile = "$tmpDir/extracted.arazzo.json";
            ob_start();
            $this->assertEquals(0, \Cdd\Cli\CddCli::generate_to_arazzo(['-i', $outSdk, '-o', $extractedFile]));
            $cfgTo = new \Cdd\Cli\CddConfig();
            $cfgTo->input = "$outSdk/src/arazzo_cli.php";
            $this->assertEquals(0, \Cdd\Cli\CddCli::generate_to_arazzo($cfgTo));
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', "$tmpDir/out_server/src/WorkflowEngine.php"]));
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', '-i', "$outSdk/src/ApiWorkflows.php"]));
            $this->assertEquals(1, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', '-i', "$tmpDir/nonexistent"]));
            ob_end_clean();

            // Directory modes for to_arazzo when only CLI, Engine, or Test exists
            $dirCliOnly = "$tmpDir/dir_cli_only";
            mkdir("$dirCliOnly/src", 0777, true);
            copy("$outSdk/src/arazzo_cli.php", "$dirCliOnly/src/arazzo_cli.php");
            $dirEngineOnly = "$tmpDir/dir_engine_only";
            mkdir("$dirEngineOnly/src", 0777, true);
            copy("$tmpDir/out_server/src/WorkflowEngine.php", "$dirEngineOnly/src/WorkflowEngine.php");
            $dirTestOnly = "$tmpDir/dir_test_only";
            mkdir("$dirTestOnly/tests/Workflows", 0777, true);
            copy("$tmpDir/out_server/tests/Workflows/ArazzoWorkflowTest.php", "$dirTestOnly/tests/Workflows/ArazzoWorkflowTest.php");

            ob_start();
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', '-i', $dirCliOnly]));
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', '-i', $dirEngineOnly]));
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'to_arazzo', '-i', $dirTestOnly]));
            ob_end_clean();

            // Sync with Arazzo (both truth=spec and truth=class and fallback arazzo.json)
            copy($specFile, "$outSdk/arazzo.json");
            ob_start();
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'sync', '-i', $outSdk, '-t', 'spec']));
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'sync', '-i', $outSdk, '-t', 'class']));
            unlink("$outSdk/src/ApiWorkflows.php");
            $this->assertEquals(0, \Cdd\Cli\CddCli::run(['cdd-php', 'sync', '-i', $outSdk]));
            ob_end_clean();

            // MCP tools/call for from_arazzo and to_arazzo
            $inPipe = fopen('php://memory', 'r+');
            if ($inPipe !== false) {
                fwrite($inPipe, json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'from_arazzo', 'arguments' => ['input' => $specFile, 'output' => "$tmpDir/mcp_out"]]]) . "
");
                fwrite($inPipe, json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'to_arazzo', 'arguments' => ['input' => "$tmpDir/mcp_out", 'output' => "$tmpDir/mcp_arazzo.json"]]]) . "
");
                rewind($inPipe);
                \Cdd\Cli\CddCli::$testInStream = $inPipe;
                ob_start();
                \Cdd\Cli\CddCli::runMcpServer();
                $mcpOut = (string) ob_get_clean();
                fclose($inPipe);
                \Cdd\Cli\CddCli::$testInStream = null;
                $this->assertStringContainsString('Emitted Arazzo', $mcpOut);
            }
        } finally {
            $this->removeDir($tmpDir);
        }
    }
}
