<?php

/**
 * Tests for WASM bundle filtering (`scripts/build_wasm_bundle.php`) and
 * custom section stripping/bundling (`scripts/bundle_wasm_payload.php`).
 *
 * @package Cdd\Tests\Scripts
 */

declare(strict_types=1);

namespace Cdd\Tests\Scripts;

use Cdd\Tests\Framework\TestCase;

require_once __DIR__ . '/../../scripts/build_wasm_bundle.php';
require_once __DIR__ . '/../../scripts/bundle_wasm_payload.php';

/**
 * Verifies LEB128 custom section stripping/appending and minimal runtime bundle filtering.
 */
class BundleWasmPayloadTest extends TestCase
{
    /**
     * Tests bundling a payload into a minimal WASM binary and stripping debug custom sections.
     *
     * @return void
     */
    public function testBundlingAndCustomSectionStripping(): void
    {
        $wasmFile = sys_get_temp_dir() . '/test_bundle.wasm';
        $payloadFile = sys_get_temp_dir() . '/test_payload.txt';

        $debugSec = build_wasm_custom_section('.debug_info', 'debug_bytes');
        $keepSec = build_wasm_custom_section('target_features', 'features');
        $codeSec = chr(10) . encode_leb128_u(4) . "\x01\x02\x03\x04";

        file_put_contents($wasmFile, "\x00asm\x01\x00\x00\x00" . $codeSec . $debugSec . $keepSec);
        file_put_contents($payloadFile, 'test_payload_data');

        $script = (string) realpath(__DIR__ . '/../../scripts/bundle_wasm_payload.php');
        $phpBin = PHP_SAPI === 'phpdbg' ? (string) preg_replace('/phpdbg([0-9.]*)$/', 'php$1', PHP_BINARY) : PHP_BINARY;
        exec(escapeshellarg($phpBin) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($wasmFile) . ' ' . escapeshellarg($payloadFile), $output, $ret);

        $this->assertEquals(0, $ret);

        $bundled = (string) file_get_contents($wasmFile);
        $this->assertTrue(str_contains($bundled, 'cdd-php-payload'));
        $this->assertTrue(str_contains($bundled, 'test_payload_data'));
        $this->assertTrue(str_contains($bundled, 'target_features'));
        $this->assertFalse(str_contains($bundled, '.debug_info'));

        @unlink($wasmFile);
        @unlink($payloadFile);
    }

    /**
     * Tests LEB128 round-trip encoding/decoding and runtime file filtering helpers.
     *
     * @return void
     */
    public function testHelpers(): void
    {
        $encoded = encode_leb128_u(624485);
        $offset = 0;
        $decoded = decode_leb128_u($encoded, $offset);
        $this->assertEquals(624485, $decoded);
        $this->assertEquals(strlen($encoded), $offset);

        $this->assertEquals('short', strip_wasm_custom_sections('short'));

        $this->assertTrue(is_runtime_bundle_file('bin/cdd-php'));
        $this->assertFalse(is_runtime_bundle_file('bin/check_docs.php'));
        $this->assertTrue(is_runtime_bundle_file('src/cli/CddCli.php'));
        $this->assertTrue(is_runtime_bundle_file('vendor/nikic/php-parser/lib/PhpParser/Node.php'));
        $this->assertFalse(is_runtime_bundle_file('vendor/fakerphp/faker/src/Faker/Factory.php'));
        $this->assertFalse(is_runtime_bundle_file('vendor/phpunit/phpunit/phpunit'));

        $autoload = build_minimal_autoload_php();
        $this->assertTrue(str_contains($autoload, 'PhpParser'));
        $this->assertTrue(str_contains($autoload, 'src/cli/CddCli.php'));
    }
}
