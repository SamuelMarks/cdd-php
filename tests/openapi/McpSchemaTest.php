<?php

declare(strict_types=1);

namespace Cdd\Tests\Openapi;

use Cdd\Tests\Framework\TestCase;

class McpSchemaTest extends TestCase
{
    public function testMcpSchemaGenerated()
    {
        $outDir = sys_get_temp_dir() . '/cdd_mcp_schema_' . uniqid();
        mkdir($outDir);
        mkdir("$outDir/src");

        $openapi = ['openapi' => '3.2.0', 'info' => ['title' => 'A', 'version' => '1'], 'paths' => []];
        $options = [
            'subcommand' => 'to_server'
        ];

        $json = \Cdd\Openapi\emit($openapi, $outDir, $options);
        $decoded = json_decode($json, true);

        $this->assertTrue(isset($decoded['components']['schemas']['JSONRPCRequest']));
        $this->assertTrue(isset($decoded['components']['schemas']['JSONRPCResponse']));
        $this->assertTrue(isset($decoded['components']['schemas']['CallToolRequest']));

        $this->assertTrue(file_exists("$outDir/src/Models/JSONRPCRequest.php"));
        $this->assertTrue(file_exists("$outDir/src/Models/CallToolRequest.php"));
        $modelsCode = file_get_contents("$outDir/src/Models/JSONRPCRequest.php");
        $this->assertTrue(strpos($modelsCode, 'class JSONRPCRequest') !== false);

        // cleanup
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($outDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($outDir);
    }
}
