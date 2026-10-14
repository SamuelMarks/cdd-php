<?php

/**
 * Tests for bundling a payload into a WebAssembly module custom section (`scripts/bundle_wasm_payload.php`).
 *
 * @package Cdd\Tests\Scripts
 */

declare(strict_types=1);

namespace Cdd\Tests\Scripts;

use Cdd\Tests\Framework\TestCase;

/**
 * Verifies LEB128 custom section appending in `scripts/bundle_wasm_payload.php`.
 */
class BundleWasmPayloadTest extends TestCase
{
    /**
     * Tests bundling a payload into a minimal WASM binary.
     *
     * @return void
     */
    public function testBundling(): void
    {
        $wasmFile = sys_get_temp_dir() . '/test_bundle.wasm';
        $payloadFile = sys_get_temp_dir() . '/test_payload.txt';

        file_put_contents($wasmFile, "\x00asm\x01\x00\x00\x00");
        file_put_contents($payloadFile, 'test_payload_data');

        $script = (string) realpath(__DIR__ . '/../../scripts/bundle_wasm_payload.php');
        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg$/', 'php', PHP_BINARY) : PHP_BINARY;
        exec(escapeshellarg($phpBin) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($wasmFile) . ' ' . escapeshellarg($payloadFile), $output, $ret);

        $this->assertEquals(0, $ret);

        $bundled = (string) file_get_contents($wasmFile);
        $this->assertTrue(str_contains($bundled, 'cdd-php-payload'));
        $this->assertTrue(str_contains($bundled, 'test_payload_data'));

        @unlink($wasmFile);
        @unlink($payloadFile);
    }
}
