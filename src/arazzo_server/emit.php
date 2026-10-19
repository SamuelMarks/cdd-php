<?php

/**
 * Emits Server-Side Workflow Engine (`WorkflowEngine.php`) and E2E Workflow PHPUnit Tests (`ArazzoWorkflowTest.php`).
 *
 * @package Cdd\ArazzoServer
 */

declare(strict_types=1);

namespace Cdd\ArazzoServer;

use function Cdd\ArazzoClient\toCamelCaseIdentifier;

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
    $version = (string) ($arazzo['arazzo'] ?? '1.1.0');
    $info = is_array($arazzo['info'] ?? null) ? $arazzo['info'] : ['title' => 'Arazzo Workflows', 'version' => '1.0.0'];
    $sources = is_array($arazzo['sourceDescriptions'] ?? null) ? $arazzo['sourceDescriptions'] : [];
    $components = is_array($arazzo['components'] ?? null) ? $arazzo['components'] : [];
    $rootExt = \Cdd\Arazzo\extractExtensions($arazzo);

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
    $out .= "    /** @var string Arazzo specification version. */
";
    $out .= "    public const ARAZZO_VERSION = " . var_export($version, true) . ";

";
    $out .= "    /** @var string|null Self URI of the Arazzo document. */
";
    $out .= "    public const SELF_URI = " . var_export($arazzo['$self'] ?? null, true) . ";

";
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

    foreach ($arazzo['workflows'] ?? [] as $wf) {
        $wfId = (string) ($wf['workflowId'] ?? 'workflow');
        $methodSuffix = ucfirst(toCamelCaseIdentifier($wfId));
        $seedInputs = isset($wf['inputs']) && is_array($wf['inputs'])
            ? generateSeedInputsFromSchema($wf['inputs'])
            : [];
        $seedExport = var_export($seedInputs, true);
        $wfExport = var_export($wf, true);

        $out .= "
    /**
";
        $out .= "     * E2E workflow test for {$wfId}.
";
        $out .= "     *
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
        $out .= "        \$workflow = {$wfExport};
";
        $out .= "        \$engine = new \Api\WorkflowEngine();
";
        $out .= "        \$inputs = {$seedExport};
";
        $out .= "        \$outputs = \$engine->executeWorkflow(\$workflow['workflowId'], \$inputs);
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
