<?php

/**
 * Unit tests for `DocsJsonGenerator` verifying valid PHP snippet syntax and wrapper output.
 *
 * @package Cdd\Tests\Cli
 */

declare(strict_types=1);

namespace Cdd\Tests\Cli;

use Cdd\Classes;
use Cdd\Cli\DocsJsonGenerator;
use Cdd\Tests\Framework\TestCase;

/**
 * Tests `DocsJsonGenerator` requestBody property rendering and wrapper cleanup.
 */
class DocsJsonGeneratorTest extends TestCase
{
    /**
     * Verifies that generated snippets for `requestBody` operations contain valid PHP syntax
     * and omit empty `wrapper_end` fields.
     *
     * @return void
     */
    public function testRequestBodyValidPhpSyntaxAndWrapperOmission(): void
    {
        $tempSpec = sys_get_temp_dir() . '/cdd_docs_gen_' . uniqid() . '.json';
        $spec = [
            'openapi' => '3.2.0',
            'paths' => [
                '/items' => [
                    'post' => [
                        'operationId' => 'createItem',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string'],
                                            'count' => ['type' => 'integer'],
                                            'price' => ['type' => 'number'],
                                            'active' => ['type' => 'boolean'],
                                            'tags' => ['type' => 'array'],
                                            'meta' => ['type' => 'object'],
                                            'role' => ['type' => 'string', 'default' => 'admin'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Created'],
                        ],
                    ],
                    'put' => [
                        'operationId' => 'replaceItem',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Replaced'],
                        ],
                    ],
                ],
            ],
            'workflows' => [
                [
                    'workflowId' => 'item-lifecycle',
                    'inputs' => [
                        'type' => 'object',
                        'properties' => [
                            'itemId' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
        file_put_contents($tempSpec, (string) json_encode($spec));

        $resolvePath = static fn (string $p): string => $p;

        try {
            ob_start();
            $status = DocsJsonGenerator::run(['cdd-php', 'to_docs_json', '-i', $tempSpec], $resolvePath);
            $out = (string) ob_get_clean();

            $this->assertEquals(0, $status);
            $decoded = json_decode($out, true);
            $this->assertTrue(is_array($decoded));

            $ops = $decoded[0]['operations'];
            $this->assertCount(3, $ops);

            $createOp = $ops[0];
            $this->assertEquals('createItem', $createOp['operationId']);
            $this->assertFalse(isset($createOp['code']['wrapper_end']));
            $this->assertTrue(isset($createOp['code']['wrapper_start']));
            $this->assertStringContainsString(
                "'body' => ['title' => 'value', 'count' => 0, 'price' => 0.0, 'active' => true, 'tags' => [], 'meta' => [], 'role' => 'admin']",
                (string) $createOp['code']['snippet']
            );

            $replaceOp = $ops[1];
            $this->assertEquals('replaceItem', $replaceOp['operationId']);
            $this->assertFalse(isset($replaceOp['code']['wrapper_end']));
            $this->assertStringContainsString("'body' => []", (string) $replaceOp['code']['snippet']);

            $wfOp = $ops[2];
            $this->assertEquals('item-lifecycle', $wfOp['operationId']);
            $this->assertFalse(isset($wfOp['code']['wrapper_end']));
            $this->assertTrue(isset($wfOp['code']['wrapper_start']));

            // Verify every generated snippet parses as valid PHP AST
            $parser = Classes\create_parser();
            foreach ($ops as $op) {
                $fullPhp = (string) ($op['code']['imports'] ?? "<?php
")
                    . "
" . (string) ($op['code']['wrapper_start'] ?? '')
                    . "
" . (string) $op['code']['snippet'];
                $ast = $parser->parse($fullPhp);
                $this->assertNotNull($ast);
            }
        } finally {
            @unlink($tempSpec);
        }
    }
}
