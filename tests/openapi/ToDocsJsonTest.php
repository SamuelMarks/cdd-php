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
                    'get' => [
                        'operationId' => 'getPet',
                        'responses' => [
                            '200' => ['description' => 'ok'],
                        ],
                    ],
                ],
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
        $this->assertEquals(1, count($operations));

        $op = $operations[0];
        $this->assertEquals('GET', $op['method']);
        $this->assertEquals('/pet', $op['path']);
        $this->assertEquals('getPet', $op['operationId']);

        $code = $op['code'];
        $this->assertTrue(isset($code['snippet']));
        $this->assertTrue(!isset($code['imports']));
        $this->assertTrue(!isset($code['wrapper_start']));
        $this->assertTrue(!isset($code['wrapper_end']));

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
        $this->assertTrue(isset($code['wrapper_start']));
        $this->assertTrue(isset($code['wrapper_end']));

        @unlink($tempSpec);
    }
}
