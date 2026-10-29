<?php

/**
 * Tests for `to_docs_json` CLI command flags and JSON structure.
 *
 * @package Cdd\Tests\Openapi
 */

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

/**
 * Verifies `to_docs_json` with and without `--no-imports` and `--no-wrapping`.
 */
class ToDocsJsonTest extends TestCase
{
    /**
     * Creates a temporary OpenAPI spec file for testing `to_docs_json`.
     *
     * @return string Path to the temporary spec file.
     */
    private function getSpecFile(): string
    {
        $tempSpec = sys_get_temp_dir() . '/test_spec_' . uniqid() . '.json';
        $spec = [
            'openapi' => '3.2.0',
            'paths' => [
                '/pet' => [
                    'summary' => 'Pet endpoint',
                    'get' => [
                        'operationId' => 'getPet',
                        'parameters' => [
                            ['name' => 'petId', 'in' => 'query'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'ok'],
                        ],
                    ],
                    'additionalOperations' => [
                        'PURGE' => [
                            'operationId' => 'purge_pet',
                            'requestBody' => [
                                'content' => ['application/json' => ['schema' => ['type' => 'object']]],
                            ],
                            'responses' => [
                                '200' => ['description' => 'purged'],
                            ],
                        ],
                    ],
                ],
                '/non-array' => 'invalid-item',
            ],
            'webhooks' => [
                'newPet' => [
                    'post' => [
                        'operationId' => 'onNewPet',
                        'requestBody' => [
                            'content' => ['application/json' => ['schema' => ['type' => 'object']]],
                        ],
                        'responses' => [
                            '200' => ['description' => 'ok'],
                        ],
                    ],
                ],
                'unnamedWebhook' => [
                    'post' => [
                        'responses' => [
                            '200' => ['description' => 'ok'],
                        ],
                    ],
                ],
            ],
            'workflows' => [
                [
                    'workflowId' => 'adoption-flow',
                    'inputs' => [
                        'type' => 'object',
                        'properties' => [
                            'petId' => ['type' => 'string'],
                        ],
                    ],
                    'steps' => [
                        ['stepId' => 'fetchPet', 'operationId' => 'getPet'],
                    ],
                ],
                'invalid-workflow-entry',
            ],
        ];
        file_put_contents($tempSpec, (string) json_encode($spec));
        return $tempSpec;
    }

    /**
     * Tests `to_docs_json` with `--no-imports` and `--no-wrapping`.
     *
     * @return void
     */
    public function testToDocsJsonWithFlags(): void
    {
        $binPath = dirname(__DIR__, 2) . '/bin/cdd-php';
        $tempSpec = $this->getSpecFile();

        $cmd = escapeshellcmd("php {$binPath} to_docs_json --no-imports --no-wrapping -i {$tempSpec}");
        $output = (string) shell_exec($cmd);
        $json = json_decode($output, true);

        $this->assertTrue(is_array($json));
        $this->assertEquals(1, count($json));
        $this->assertEquals('php', $json[0]['language']);

        $operations = $json[0]['operations'];
        $this->assertEquals(5, count($operations));

        $op = $operations[0];
        $this->assertEquals('GET', $op['method']);
        $this->assertEquals('/pet', $op['path']);
        $this->assertEquals('getPet', $op['operationId']);

        $code = $op['code'];
        $this->assertTrue(isset($code['snippet']));
        $this->assertStringContainsString("'petId' => 'value'", (string) $code['snippet']);
        $this->assertTrue(!isset($code['imports']));
        $this->assertTrue(!isset($code['wrapper_start']));
        $this->assertTrue(!isset($code['wrapper_end']));

        $purgeOp = $operations[1];
        $this->assertEquals('PURGE', $purgeOp['method']);
        $this->assertEquals('purge_pet', $purgeOp['operationId']);
        $this->assertStringContainsString('$client->purgePet(', (string) $purgeOp['code']['snippet']);

        $webhookOp = $operations[2];
        $this->assertEquals('POST', $webhookOp['method']);
        $this->assertEquals('newPet', $webhookOp['path']);
        $this->assertEquals('onNewPet', $webhookOp['operationId']);

        $unnamedWebhookOp = $operations[3];
        $this->assertEquals('webhook-postunnamedWebhook', $unnamedWebhookOp['operationId']);

        $workflowOp = $operations[4];
        $this->assertEquals('WORKFLOW', $workflowOp['method']);
        $this->assertEquals('/workflows/adoption-flow', $workflowOp['path']);
        $this->assertEquals('adoption-flow', $workflowOp['operationId']);
        $this->assertStringContainsString('$workflows->adoptionFlow(', (string) $workflowOp['code']['snippet']);
        $this->assertFalse(isset($workflowOp['code']['imports']));
        $this->assertFalse(isset($workflowOp['code']['wrapper_start']));

        @unlink($tempSpec);
    }

    /**
     * Tests `to_docs_json` default output including imports and wrapper blocks.
     *
     * @return void
     */
    public function testToDocsJsonWithoutFlags(): void
    {
        $binPath = dirname(__DIR__, 2) . '/bin/cdd-php';
        $tempSpec = $this->getSpecFile();

        $cmd = escapeshellcmd("php {$binPath} to_docs_json -i {$tempSpec}");
        $output = (string) shell_exec($cmd);
        $json = json_decode($output, true);

        $this->assertTrue(is_array($json));
        $code = $json[0]['operations'][0]['code'];

        $this->assertTrue(isset($code['snippet']));
        $this->assertTrue(isset($code['imports']));
        $this->assertStringContainsString('<?php', (string) $code['imports']);
        $this->assertStringContainsString('use Api\ApiClient;', (string) $code['imports']);
        $this->assertTrue(isset($code['wrapper_start']));
        $this->assertTrue(isset($code['wrapper_end']));

        $wfCode = $json[0]['operations'][4]['code'];
        $this->assertStringContainsString('use Api\ApiWorkflows;', (string) $wfCode['imports']);
        $this->assertStringContainsString('$workflows = new ApiWorkflows($client);', (string) $wfCode['wrapper_start']);

        @unlink($tempSpec);
    }

    /**
     * Tests `DocsJsonGenerator::run` directly with environment variables and directory output resolution.
     *
     * @return void
     */
    public function testDocsJsonGeneratorEnvVarsAndDirectoryOutput(): void
    {
        $tempSpec = $this->getSpecFile();
        $outDir = sys_get_temp_dir() . '/cdd_docs_dir_' . uniqid();
        mkdir($outDir, 0777, true);

        $resolvePath = static fn (string $p): string => $p;

        ob_start();
        $defaultStatus = \Cdd\Cli\DocsJsonGenerator::run(['cdd-php', 'to_docs_json', '-i', $tempSpec], $resolvePath);
        $defaultOut = (string) ob_get_clean();
        $this->assertEquals(0, $defaultStatus);
        $defaultJson = json_decode($defaultOut, true);
        $this->assertTrue(is_array($defaultJson));
        $this->assertStringContainsString('use Api\ApiWorkflows;', (string) $defaultJson[0]['operations'][4]['code']['imports']);

        $_ENV['CDD_INPUT'] = $tempSpec;
        $_SERVER['CDD_OUTPUT'] = $outDir;
        putenv('CDD_NO_IMPORTS=true');
        putenv('CDD_NO_WRAPPING=1');

        try {
            $status = \Cdd\Cli\DocsJsonGenerator::run(['cdd-php', 'to_docs_json'], $resolvePath);
            $this->assertEquals(0, $status);
            $this->assertTrue(file_exists($outDir . '/docs.json'));

            $decoded = json_decode((string) file_get_contents($outDir . '/docs.json'), true);
            $this->assertTrue(is_array($decoded));
            $this->assertEquals('php', $decoded[0]['language']);
            $this->assertFalse(isset($decoded[0]['operations'][0]['code']['imports']));
            $this->assertFalse(isset($decoded[0]['operations'][0]['code']['wrapper_start']));
        } finally {
            unset($_ENV['CDD_INPUT'], $_SERVER['CDD_OUTPUT']);
            putenv('CDD_NO_IMPORTS');
            putenv('CDD_NO_WRAPPING');
            @unlink($tempSpec);
            $this->removeDir($outDir);
        }
    }
}
