# WASM Support

WASM support for a PHP CLI project is **possible**, but requires compiling the entire PHP interpreter from source using Emscripten (`emcc`). 

Because `cdd-php` is written in PHP, we cannot simply compile the PHP scripts directly to WebAssembly. Instead, we must bundle our PHP scripts with a WASM-compiled PHP runtime.

## Integration into unified CLI / Web Interfaces
Once the PHP interpreter is compiled to WASM, it can be executed in Node.js, Deno, or browsers, and we can pass the `cdd-php` source files to it via a virtual file system.

To build this yourself, you would:
1. Obtain the PHP source code.
2. Use `../emsdk` to compile PHP with `emconfigure ./configure` and `emmake make`.
3. Package `bin/cdd-php` and `src/` into a single `cdd-php.phar`.
4. Run the WASM PHP engine passing the phar file.

Currently, the `make build_wasm` command is a stub that validates the `emsdk` environment but does not fully build the PHP engine, as it requires downloading and patching the PHP source.