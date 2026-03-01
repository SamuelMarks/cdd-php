# WASM Build

This document outlines the steps to build the `cdd-php` project into a WebAssembly (WASM) binary using Emscripten.

## Why WASM?

A WASM build of `cdd-php` allows the tooling to be executed:
- Within a unified CLI of all `cdd-*` projects on systems without PHP installed.
- Inside a web browser for a unified web interface, enabling OpenAPI generation and parsing purely on the client side.

## Prerequisites

- Emscripten SDK (`emsdk`). It is expected to be located at `../emsdk`.
- Make, CMake, and standard C/C++ compilation tools.
- PHP source code (if compiling the PHP interpreter to WASM).

## Build Instructions

Currently, building PHP CLI natively to WebAssembly requires compiling the PHP interpreter itself with the `cdd-php` source files embedded or packaged as a virtual filesystem, or using a project like `php-wasm`.

To build, you can use the Makefile target:

```sh
source ../emsdk/emsdk_env.sh
make build_wasm
```

### Current Status

Currently, the `make build_wasm` target is a placeholder. Building PHP to WASM with full standard library support and bundling the CDD-PHP source code as a Phar or virtual filesystem is an experimental process.

*Is it possible?* Yes, projects like `php-wasm` (by WordPress or others) have successfully compiled PHP to WASM.
*Is it implemented here?* Not yet fully implemented. The build process needs to be finalized to output a `.wasm` and `.js` file for seamless integration.
