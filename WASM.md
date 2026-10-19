# WebAssembly (WASM) Support

The `cdd-php` compiler can be packaged into a single self-contained WebAssembly (WASM/WASI) binary (`build/wasm/cdd-php.wasm`) to run in browsers, Node.js, or WASI runtimes (`wasmtime`, `wasmer`) without a local PHP installation.

| Feature                      | Possible | Implemented |
| ---------------------------- | -------- | ----------- |
| WASI / WASM Build            | Yes      | Yes         |
| Custom-Section VFS Embedding | Yes      | Yes         |
| Browser & JS Integration     | Yes      | Yes         |

## How the WASM Build Works

1. **PHAR Build (`scripts/build_phar.php`)**: Packages `src/`, `bin/`, and runtime dependencies into `build/cdd-php.phar`.
2. **Pre-Compiled PHP WASI Runtime**: Downloads the slim PHP 8.2 WASI binary (`php-cgi-8.2.6-slim.wasm` from `vmware-labs/webassembly-language-runtimes`).
3. **Virtual Filesystem Bundle (`scripts/build_wasm_bundle.php`)**: Serializes `src/`, `vendor/`, `bin/`, and CLI entrypoints (`cdd-php`, `from_openapi`, `to_docs_json`, `php.ini`) into `build/wasm_bundle.json`.
4. **WASM Custom Section Injection (`scripts/bundle_wasm_payload.php`)**: Appends a WebAssembly custom section (section ID `0`, named `"cdd_bundle"`) containing the LEB128-encoded JSON filesystem payload directly into `build/wasm/cdd-php.wasm`.

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
