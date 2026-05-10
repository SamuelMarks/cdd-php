<?php

namespace Cdd\Tests\Scripts;

use Cdd\Tests\Framework\TestCase;

class BundleWasmPayloadTest extends TestCase
{
    public function testBundling()
    {
        $wasmFile = sys_get_temp_dir() . '/test_bundle.wasm';
        $payloadFile = sys_get_temp_dir() . '/test_payload.txt';
        
        file_put_contents($wasmFile, "\x00asm\x01\x00\x00\x00"); // Valid empty wasm
        file_put_contents($payloadFile, "test_payload_data");
        
        $script = realpath(__DIR__ . '/../../scripts/bundle_wasm_payload.php');
        exec("php " . escapeshellarg($script) . " " . escapeshellarg($wasmFile) . " " . escapeshellarg($payloadFile), $output, $ret);
        
        $this->assertEquals(0, $ret);
        
        $bundled = file_get_contents($wasmFile);
        $this->assertTrue(str_contains($bundled, "cdd-php-payload"));
        $this->assertTrue(str_contains($bundled, "test_payload_data"));
        
        @unlink($wasmFile);
        @unlink($payloadFile);
    }
}
