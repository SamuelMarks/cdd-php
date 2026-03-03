# WebAssembly (WASM) Support

The `cdd-php` project aims to be accessible everywhere, including in a browser or a system without PHP natively installed, through WebAssembly (WASM).

| Feature         | Possible | Implemented |
| --------------- | -------- | ----------- |
| WASM Build      | Yes      | Yes         |
| Browser Support | Yes      | Yes         |
| JS Integration  | Yes      | Yes         |

## Building WASM

We utilize `emscripten` to compile the PHP runtime with the `cdd-php` codebase embedded.

To build the WASM binary, ensure `emsdk` is installed in `../emsdk` relative to this project, and run:

```sh
make build_wasm
```

Or on Windows:

```cmd
make.bat build_wasm
```

The compiled `cdd-php.wasm` will be available in the `build/wasm` directory.
