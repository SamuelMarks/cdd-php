# WebAssembly (WASM) Support

The `cdd-php` compiler can be packaged into a single self-contained WebAssembly (WASM/WASI) binary (`build/wasm/cdd-php.wasm`) to run in browsers, Node.js, or WASI runtimes (`wasmtime`, `wasmer`) without a local PHP installation.

| Feature                      | Possible | Implemented |
| ---------------------------- | -------- | ----------- |
| WASI / WASM Build            | Yes      | Yes         |
| Custom-Section VFS Embedding | Yes      | Yes         |
| Browser & JS Integration     | Yes      | Yes         |

## How the WASM Build Works

1. **PHAR Build (`scripts/build_phar.php`)**: Packages stripped `src/`, `bin/cdd-php`, `nikic/php-parser`, and a minimal PSR-4 `vendor/autoload.php` into `build/cdd-php.phar` (~1.1 MB).
2. **Pre-Compiled PHP WASI Runtime**: Downloads the slim PHP 8.2 WASI binary (`php-cgi-8.2.6-slim.wasm` from `vmware-labs/webassembly-language-runtimes`) and optimizes it with `wasm-opt -Oz --strip-debug --strip-producers` when `binaryen` is installed.
3. **Minimal Virtual Filesystem Bundle (`scripts/build_wasm_bundle.php`)**: Filters out dev/test-only `vendor/` packages (`fakerphp/faker`, `phpunit`, `php-cs-fixer`, `illuminate/*`, `carbon`, `symfony/*`), strips PHP comments and whitespace via `php_strip_whitespace()`, injects a minimal PSR-4 autoloader for `nikic/php-parser`, and serializes the VFS with `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` into `build/wasm_bundle.json` (~1.05 MB vs. 39 MB unoptimized).
4. **WASM Custom Section Injection (`scripts/bundle_wasm_payload.php`)**: Strips non-essential `.debug_*` and `producers` custom sections from the base WASM module and appends a custom section (section ID `0`, named `"cdd-php-payload"`) containing the LEB128-encoded JSON filesystem payload directly into `build/wasm/cdd-php.wasm` (~6.8 MB total vs. 46 MB unoptimized).

## Building WASM

Run the `build_wasm` target via `make`:

```sh
make build_wasm
```

Or on Windows:

```cmd
make.bat build_wasm
```

The resulting self-contained WebAssembly module is written to `build/wasm/cdd-php.wasm`.
