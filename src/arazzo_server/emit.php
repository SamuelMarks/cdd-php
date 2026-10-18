<?php

/**
 * Emits Server-Side Workflow Engine (`WorkflowEngine.php`) and E2E Workflow PHPUnit Tests (`ArazzoWorkflowTest.php`).
 *
 * @package Cdd\ArazzoServer
 */

declare(strict_types=1);

namespace Cdd\ArazzoServer;

/**
 * Generates sample seed input values from a JSON Schema 2020-12 object.
 *
 * @param array<string, mixed> $schema JSON Schema object.
 * @return array<string, mixed> Seed input dictionary.
 */
function generateSeedInputsFromSchema(array $schema): array
{
    $seeds = [];
    $props = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
    foreach ($props as $propName => $propSchema) {
        if (!is_array($propSchema)) {
            $seeds[(string) $propName] = 'sample';
            continue;
        }
        $type = (string) ($propSchema['type'] ?? 'string');
        $seeds[(string) $propName] = match ($type) {
            'integer' => 1,
            'number' => 1.5,
            'boolean' => true,
            'array' => [],
            'object' => [],
            default => (string) ($propSchema['default'] ?? 'sample_' . $propName),
        };
    }
    return $seeds;
}

/**
 * Emits the server-side `WorkflowEngine.php` orchestrator and stateful mock handler class.
 *
 * @param array<string, mixed> $arazzo Normalized Arazzo 1.1.0 specification array.
 * @return string Generated PHP code for `src/WorkflowEngine.php`.
 */
function emitWorkflowEngine(array $arazzo): string
{
    $sdkCode = \Cdd\ArazzoClient\emit($arazzo);
    return str_replace('class ApiWorkflows', 'class WorkflowEngine', $sdkCode);
}

/**
 * Emits an automated E2E PHPUnit workflow test suite (`ArazzoWorkflowTest.php`) from an Arazzo specification.
 *
 * @param array<string, mixed> $arazzo Normalized Arazzo 1.1.0 specification array.
 * @return string Generated PHP code for `tests/Workflows/ArazzoWorkflowTest.php`.
 */
function emit(array $arazzo): string
{
    $out = "<?php

";
    $out .= "/**
";
    $out .= " * Auto-generated Arazzo E2E Server Workflow Test Suite
";
    $out .= " *
";
    $out .= " * @package Api\Tests\Workflows
";
    $out .= " * @arazzo-spec " . json_encode($arazzo, JSON_UNESCAPED_SLASHES) . "
";
    $out .= " */

";
    $out .= "declare(strict_types=1);

";
    $out .= "namespace Api\Tests\Workflows;

";
    $out .= "use PHPUnit\Framework\TestCase;

";
    $out .= "/**
 * E2E Workflow Orchestration & Conformance Test Suite.
 */
";
    $out .= "class ArazzoWorkflowTest extends TestCase
{
";
    $out .= "    /**
     * Returns the Arazzo specification under test.
     *
     * @return array<string, mixed>
     */
";
    $out .= "    public static function getSpecification(): array
    {
";
    $out .= "        return " . var_export($arazzo, true) . ";
";
    $out .= "    }
";

    foreach ($arazzo['workflows'] ?? [] as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $methodSuffix = ucfirst(\Cdd\ArazzoClient\toCamelCaseIdentifier($wfId));
        $seedInputs = isset($wf['inputs']) && is_array($wf['inputs'])
            ? generateSeedInputsFromSchema($wf['inputs'])
            : [];
        $seedExport = var_export($seedInputs, true);

        $out .= "
    /**
";
        $out .= "     * E2E workflow test for {$wfId}.
";
        $out .= "     *
";
        $out .= "     * @arazzo-workflow " . json_encode($wf, JSON_UNESCAPED_SLASHES) . "
";
        $out .= "     * @return void
";
        $out .= "     */
";
        $out .= "    public function testWorkflow{$methodSuffix}(): void
    {
";
        $out .= "        require_once __DIR__ . '/../../src/WorkflowEngine.php';
";
        $out .= "        \$engine = new \Api\WorkflowEngine();
";
        $out .= "        \$inputs = {$seedExport};
";
        $out .= "        \$outputs = \$engine->executeWorkflow(" . var_export($wfId, true) . ", \$inputs);
";
        $out .= "        \$this->assertIsArray(\$outputs);
";
        $out .= "    }
";
    }

    $out .= "}
";
    return $out;
}
