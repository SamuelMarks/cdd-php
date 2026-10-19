cdd-php: PHP ⇄ OpenAPI
======================

[![License](https://img.shields.io/badge/license-Apache--2.0%20OR%20MIT-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![interactive WASM web demo](https://img.shields.io/badge/interactive-WASM_web_demo-blue.svg)](https://offscale.io/wasm_web_demo)
[![CI](https://github.com/SamuelMarks/cdd-php/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-php/actions)
[![Test Coverage](https://img.shields.io/badge/test_coverage-100%25-brightgreen.svg)](#)
[![Doc Coverage](https://img.shields.io/badge/doc_coverage-100%25-brightgreen.svg)](#)

**cdd-php** compiles **[OpenAPI](https://spec.openapis.org/oas/latest.html) / [Swagger](https://spec.openapis.org/oas/v2.0.html) / [Arazzo](https://spec.openapis.org/arazzo/latest.html)** to and from **PHP** (servers, client SDKs, CLIs, database schemas, tests, mocks, and docs), updating your existing code in-place without overwriting your edits.

**Compiler Driven Development (CDD)** is a software engineering methodology that uses a suite of standalone, lossless compilers to synchronize API specifications, server implementations, client SDKs, command-line tooling, workflow orchestrators, tests, and documentation.

Unlike traditional code generators that treat outputs as disposable or read-only, Compiler Driven Development provides a **complete, standalone compiler** (exposed as both a CLI and a programmatic SDK) written natively in each supported language. Where conventional compilers rely solely on an Abstract Syntax Tree (AST) that discards formatting and comments, CDD compilers operate on a **Concrete Syntax Tree (CST)** or **Decorated Syntax Tree (DST)**. This guarantees **lossless conversion**—preserving exact whitespace, indentation, inline comments, block comments, and docstrings—allowing true bidirectional synchronization between hand-edited `PHP` source code and [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html), [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html), and [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html) specifications.

---

## Why Compiler Driven Development?

By replacing one-way templates and manual boilerplate with lossless CST/DST compilers, Compiler Driven Development transforms the engineering lifecycle across four dimensions:

- **Development Speed**: Eliminate manual boilerplate across servers, client SDKs, CLIs, database models, workflow runners, and mock servers. Start from an [OpenAPI](https://spec.openapis.org/oas/latest.html) or [Arazzo](https://spec.openapis.org/arazzo/latest.html) specification to scaffold an entire installable project (complete with package manifests and CI workflows), or write code first in `PHP` and extract the specification automatically. When requirements change, update whichever representation is most convenient and synchronize the rest immediately.
- **Development Consistency**: Every data model, route handler, CLI flag, ORM entity, workflow step, and API contract is derived from and validated against the same semantic representation. Architectural drift between backend routes, frontend clients, and published specifications becomes structurally impossible.
- **Development Quality**: Keeping tests and documentation in lockstep with implementation code is automated rather than an afterthought. Because the compiler parses and emits docstrings, type signatures, API mocks, and contract test suites alongside your business logic, maintaining **100% documentation coverage and 100% test coverage** becomes a natural byproduct of compilation rather than a manual chore.
- **Polyglot Development**: Seamlessly bridge **13+ programming languages** (C, C++, C#, Go, Java, Kotlin, PHP, Python, Ruby, Rust, POSIX Shell, Swift, and TypeScript) to and from [OpenAPI](https://spec.openapis.org/oas/latest.html) and [Arazzo](https://spec.openapis.org/arazzo/latest.html) specifications. Any codebase in any supported language can be reverse-compiled into OpenAPI/Arazzo and immediately compiled forward into idiomatic, human-editable code across every other language in the suite. And being an open-source methodology—rather than a proprietary trade secret—new languages can be added by implementing new compilers that follow the CDD approach (see [cdd-spec-conformance](https://github.com/SamuelMarks/cdd-spec-conformance) for tooling to facilitate).

---

## The Standalone Compiler Architecture and Lossless Conversion

Traditional code generation tools rely on destructive templating: if you regenerate your project from an updated specification, your custom implementation logic, formatting, and comments are overwritten.

Each Compiler Driven Development toolchain is written natively in its target language, compiles both to native binaries and to **WebAssembly (WASM/WASI)**, and implements a full lexer, parser, semantic differ, and code emitter backed by Concrete Syntax Tree (CST) and Decorated Syntax Tree (DST) representations:

- **Beyond AST to CST/DST**: A standard Abstract Syntax Tree (AST) captures program semantics while stripping away "trivia" (whitespace, blank lines, and comments). By pairing structural AST semantics with a Concrete Syntax Tree (CST) or Decorated Syntax Tree (DST) that attaches trivia and structured documentation directly to syntax nodes, the compiler preserves 100% of your source formatting and comments.
- **Zero-Destruction Guarantee**: Every part of the generated codebase is designed to be edited by humans. You can open generated routing files, data models, database entities, workflow runners, tests, and CLI commands, format them to your preferences, add inline comments, and inject custom business logic.
- **When your specification changes**: The CDD compiler parses your existing source files into a CST/DST, computes a semantic diff against the updated [OpenAPI](https://spec.openapis.org/oas/latest.html) or [Arazzo](https://spec.openapis.org/arazzo/latest.html) specification, and surgically patches in new endpoints, workflows, parameters, or fields without disturbing your custom logic, comments, or whitespace.
- **When your codebase changes**: The compiler statically analyzes your updated source code, routes, workflows, tests, and docstrings—without unsafe dynamic code execution or reflection—to extract a 100% compliant, authoritative [OpenAPI](https://spec.openapis.org/oas/latest.html) or [Arazzo](https://spec.openapis.org/arazzo/latest.html) specification.

---

## The Bidirectional Synchronization Loop

```mermaid
flowchart TD
    subgraph SingleLang ["Single-Language Acceleration & Quality (e.g., PHP)"]
        direction TB
        subgraph Outputs ["Synchronized Artifacts"]
            direction LR
            Server["Server & Routes"]
            SDK["Client SDK"]
            CLI["CLI & Workflows"]
        end

        subgraph SyncLoop ["Continuous In-Language Sync (Speed & 100% Coverage)"]
            direction LR
            Schemas["Data Models, Schemas & ORM"]
            Docs["Docstrings & API Docs"]
            TestsMocks["Contract Tests & API Mocks"]
        end

        Outputs <-->|"Zero-Boilerplate Scaffolding"| SyncLoop
        Schemas <-->|"Auto-Sync"| Docs
        Docs <-->|"Auto-Sync"| TestsMocks
        TestsMocks <-->|"Auto-Sync"| Schemas
    end

    Compiler{"cdd-php<br/>Lossless CST / DST Compiler<br/>(Preserves Whitespace & Comments)"}

    SingleLang <-->|"Surgical In-Place Patching<br/>& Static Extraction"| Compiler

    OAS[("OpenAPI 3.2.0 / Swagger 2.0 / Arazzo 1.1.0<br/>Universal Contract Hub")]

    Compiler <-->|"to_openapi / from_openapi<br/>to_arazzo / from_arazzo"| OAS

    subgraph Polyglot ["Polyglot Development at Single-Language Speed"]
        direction LR
        OtherCompilers["13+ Native CDD Compilers<br/>(C, C++, C#, Go, Java, Kotlin, PHP,<br/>Python, Ruby, Rust, Shell, Swift, TS)"]
        OtherCodebases["Idiomatic Servers, SDKs, CLIs,<br/>Schemas, Docs, Tests & Mocks<br/>Across Every Target Language"]
        OtherCompilers <-->|"Lossless CST / DST Sync"| OtherCodebases
    end

    OAS <-->|"Instant Cross-Language Sync"| OtherCompilers
```

The Compiler Driven Development lifecycle delivers compounding returns in both single-language and polyglot environments:
1. **Single-Language Speed**: Edit whichever artifact is most convenient—models/schemas, route handlers, docstrings, or tests—and let `cdd-php` surgically synchronize the rest in-place without overwriting your custom logic, comments, or whitespace.
2. **Single-Language Quality**: Because schemas, documentation, contract tests, and mocks are continuously cross-compiled rather than manually maintained, **100% doc coverage and 100% test coverage** are enforced automatically on every sync.
3. **Polyglot Development at Single-Language Speed**: Extracting an authoritative [OpenAPI](https://spec.openapis.org/oas/latest.html) or [Arazzo](https://spec.openapis.org/arazzo/latest.html) contract from one language immediately drives lossless forward-compilation across all 13+ supported languages—making multi-language SDK, CLI, and service maintenance just as fast as working in a single codebase.

---

## Core Capabilities Shared Across All CDD Compilers

Every `cdd-*` repository in the suite implements the same foundational feature set:

- **Bidirectional OpenAPI 3.2.0 & Swagger 2.0 Compilation**:
  - `from_openapi`: Generate idiomatic `PHP` Client SDKs (`to_sdk`), Client CLIs (`to_sdk_cli`), and Server stubs/routers (`to_server`) from a single specification (`-i`) or a batch directory of specifications (`--input-dir`). Automatically upgrades legacy Swagger 2.0 and OpenAPI 3.0/3.1 specifications to OpenAPI 3.2.0.
  - `to_openapi`: Statically parse existing `PHP` source files or directories to extract a compliant OpenAPI 3.2.0 specification.
- **Bidirectional Arazzo 1.1.0 Workflow Orchestration**:
  - `from_arazzo`: Compile multi-step [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html) workflow specifications (including runtime expressions, step DAGs, criterion evaluations, and failure/success actions) into `PHP` SDK workflow orchestrators (`to_sdk`), CLI workflow runners (`to_sdk_cli`), and Server/E2E workflow test suites (`to_server`).
  - `to_arazzo`: Reverse-parse `PHP` workflow orchestrators, CLI scripts, or E2E test suites back into an authoritative Arazzo 1.1.0 document.
- **In-Place Codebase Synchronization (`sync`)**:
  - Synchronize data models, ORM schemas, route handlers, docstrings, contract tests, mocks, and specifications in-place using lossless CST/DST patching.
- **Documentation Code-Snippet Generation (`to_docs_json`)**:
  - Emit a structured JSON documentation payload containing idiomatic `PHP` request/workflow code snippets for every operation in an OpenAPI specification (with `--no-imports` and `--no-wrapping` options for embedding in documentation portals).
- **Composable Contract Tests, Mocks, CI, and Package Scaffolding**:
  - By default, `from_openapi` and `from_arazzo` scaffold complete, installable `PHP` packages and `.github/workflows/ci.yml` pipelines (configurable via `--no-installable-package` and `--no-github-actions`), and generate contract test suites and API mocks/fakes via `--tests`.
- **Model Context Protocol (`mcp`) & JSON-RPC (`serve_json_rpc`)**:
  - Run `cdd-php mcp` to expose the compiler over stdio as an [MCP](https://modelcontextprotocol.io/) server for AI assistants and IDEs, or pass `--mcp` during generation to emit MCP tool/resource adapters directly inside generated SDKs and servers.
  - Run `cdd-php serve_json_rpc` to expose the compiler's full API over JSON-RPC 2.0 (`--port`, `--listen`).
- **Native CLI, Programmatic SDK, and WebAssembly (WASM/WASI)**:
  - Use `cdd-php` from the command line, import its programmatic SDK directly into `PHP` applications, or run it in the browser and WASI runtimes via its WebAssembly build (see [WASM.md](WASM.md)).

---

## The Global Polyglot Ecosystem

Every supported language operates on the same core Compiler Driven Development principles and is powered by a dedicated, native compiler tailored to that language's syntax tree, idioms, and package manager.

All implementations expose a standardized command-line interface (`cdd-php <subcommand>`) and programmatic SDK.

| Repository | Language | Targets (`to_sdk`; `to_sdk_cli`; `to_server`) | Special Features Beyond Common Subset | Standards | CI Status |
|---|---|---|---|---|---|
| [`cdd-c`](https://github.com/SamuelMarks/cdd-c) | C (C89) | Client; Client CLI; Server | 42-Language FFI Generator (`bind`); GNU-to-C89 AST Lowering; Safe CRT & Memory Security Auditor; `c-orm` Migrations | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-c/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-c/actions/workflows/ci.yml) |
| [`cdd-cpp`](https://github.com/SamuelMarks/cdd-cpp) | C++ | Client; Client CLI; Server | [Google Discovery](https://developers.google.com/discovery) (`from_google_discovery`); PostgreSQL `libpq` ORM & Faker Support | [Google Discovery](https://developers.google.com/discovery); [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-cpp/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-cpp/actions/workflows/ci.yml) |
| [`cdd-csharp`](https://github.com/SamuelMarks/cdd-csharp) | C# | Client; Client CLI; Server | CLR & Roslyn CST/DST Trivia-Preserving Sync; Entity Framework & Minimal API Targets | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-csharp/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-csharp/actions/workflows/ci.yml) |
| [`cdd-go`](https://github.com/SamuelMarks/cdd-go) | Go | Client; Client CLI; Server | Go `dst`/`ast` Surgical Patching; Multi-Source Truth Sync (`code` ⇄ `openapi`) | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-go/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-go/actions/workflows/ci.yml) |
| [`cdd-java`](https://github.com/SamuelMarks/cdd-java) | Java | Client; Client CLI; Server | JavaParser CST Lexical Preservation; WASI JSON-RPC Mode; In-Memory Compiler Pipeline | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-java/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-java/actions/workflows/ci.yml) |
| [`cdd-kotlin`](https://github.com/offscale/cdd-kotlin) | Kotlin (Ktor Multiplatform) | Client; Client CLI; Server | Multiplatform Auto-Admin UI; Ephemeral & Seeded Mock Server Modes; Webhook Triggers | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/offscale/cdd-kotlin/actions/workflows/ci.yml/badge.svg)](https://github.com/offscale/cdd-kotlin/actions/workflows/ci.yml) |
| [`cdd-php`](https://github.com/SamuelMarks/cdd-php) | PHP | Client; Client CLI; Server | Multi-Tiered DAO (`StubDao` / `ConcreteDao`); Ephemeral & Faker-Seeded SQLite Server Modes | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-php/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-php/actions/workflows/ci.yml) |
| [`cdd-python`](https://github.com/offscale/cdd-python) | Python | N/A (server building blocks) | CLI ⇄ SQLAlchemy ⇄ Pydantic ⇄ Docs ⇄ JSON Schema | N/A | [![Linting, testing, coverage, and release](https://github.com/offscale/cdd-python/workflows/Linting,%20testing,%20coverage,%20and%20release/badge.svg)](https://github.com/offscale/cdd-python/actions) |
| [`cdd-python-all`](https://github.com/offscale/cdd-python-all) | Python | Client; Client CLI; Server | `LibCST` Lossless Sync; Multi-Truth Sync (`class`, `sqlalchemy`, `function`, `openapi`); FastAPI + SQLAlchemy | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/offscale/cdd-python-all/actions/workflows/ci.yml/badge.svg)](https://github.com/offscale/cdd-python-all/actions/workflows/ci.yml) |
| [`cdd-ruby`](https://github.com/SamuelMarks/cdd-ruby) | Ruby | Client; Client CLI; Server | ActiveRecord Sync (`class`, `activerecord`, `function`); Ephemeral & Seeded Server Generation | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-ruby/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-ruby/actions/workflows/ci.yml) |
| [`cdd-rust`](https://github.com/SamuelMarks/cdd-rust) | Rust | Client; Client CLI; Server | Actix-Web, Axum & Reqwest Targets; Diesel Schema Sync; Standalone `schema-gen`, `scaffold` & `test-gen` | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-rust/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-rust/actions/workflows/ci.yml) |
| [`cdd-sh`](https://github.com/SamuelMarks/cdd-sh) | Shell (`/bin/sh`) | Client; Client CLI; Server | Zero-Dependency Embedded `sh`/`jq`/`awk` Cross-Platform Binary; Direct AST `parse` & `emit` Subcommands | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-sh/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-sh/actions/workflows/ci.yml) |
| [`cdd-swift`](https://github.com/SamuelMarks/cdd-swift) | Swift | Client; Client CLI; Server | `SwiftSyntax` Lossless Merging (`merge_swift`); Vapor + Fluent ORM Scaffolding; `sync_arazzo` | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![CI](https://github.com/SamuelMarks/cdd-swift/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-swift/actions/workflows/ci.yml) |
| [`cdd-ts`](https://github.com/offscale/cdd-ts) | TypeScript | Client; Client CLI; Server | Auto-Admin UI; Angular, React, Vue, Vanilla; Fetch, Axios, Node; Express, Bun, Deno; TypeORM Sync | [Arazzo 1.1.0](https://spec.openapis.org/arazzo/v1.1.0.html); [Swagger 2.0](https://spec.openapis.org/oas/v2.0.html); [OpenAPI 3.2.0](https://spec.openapis.org/oas/v3.2.0.html); [MCP](https://modelcontextprotocol.io/) | [![Tests and coverage](https://github.com/offscale/cdd-ts/actions/workflows/ci.yml/badge.svg)](https://github.com/offscale/cdd-ts/actions/workflows/ci.yml) |

---

## Programmatic SDK & CLI Quickstart

Every `cdd-*` toolchain can be invoked either as a standalone CLI binary or imported as a native `PHP` library.

### Minimal CLI Usage

```sh
# Print help and version
cdd-php --help
cdd-php --version

# Generate a PHP Client SDK, Client CLI, or Server from an OpenAPI spec
cdd-php from_openapi to_sdk -i spec.json -o ./sdk --tests
cdd-php from_openapi to_sdk_cli -i spec.json -o ./cli --tests
cdd-php from_openapi to_server -i spec.json -o ./server --tests

# Extract an OpenAPI 3.2.0 spec from existing PHP source code
cdd-php to_openapi -i ./src -o openapi.json

# Generate and extract Arazzo 1.1.0 multi-step workflows
cdd-php from_arazzo to_sdk -i workflow.arazzo.json -o ./workflows --tests
cdd-php from_arazzo to_sdk_cli -i workflow.arazzo.json -o ./workflow-cli --tests
cdd-php from_arazzo to_server -i workflow.arazzo.json -o ./workflow-server --tests
cdd-php to_arazzo -i ./workflows -o extracted.arazzo.json

# Synchronize codebase models, routes, tests, docs, and OpenAPI in-place
cdd-php sync -i ./src -o openapi.json

# Generate documentation-ready PHP code snippets JSON for an OpenAPI spec
cdd-php to_docs_json --no-imports --no-wrapping -i spec.json -o docs.json

# Run as a Model Context Protocol (MCP) server over stdio or JSON-RPC 2.0 server
cdd-php mcp
cdd-php serve_json_rpc --port 8080 --listen 127.0.0.1
```

### Programmatic SDK Example

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Cdd\Cli\CddCli;
use Cdd\Cli\CddConfig;

// 1. Parse an OpenAPI 3.2.0 / Swagger 2.0 document and scaffold a server or SDK directly
$spec = \Cdd\Openapi\parse((string) file_get_contents('spec.json'));
$normalizedJson = \Cdd\Openapi\emit($spec, './generated_server', [
    'subcommand' => 'to_server',
    'tests' => true,
    'mcp' => true,
]);

// 2. Programmatically invoke the compiler using typed CddConfig objects
$sdkConfig = new CddConfig();
$sdkConfig->target = 'to_sdk_cli';
$sdkConfig->input = 'spec.json';
$sdkConfig->output = './generated_sdk_cli';
$sdkConfig->tests = true;
CddCli::generate_from_openapi($sdkConfig);

// 3. Extract an OpenAPI 3.2.0 specification from PHP source code or synchronize in-place
CddCli::generate_to_openapi(['-i', './generated_server/src/routes.php', '-o', './extracted.openapi.json']);
CddCli::run(['cdd-php', 'sync', '-i', './generated_server', '-t', 'class']);

// 4. Parse and emit Arazzo 1.1.0 multi-step workflow specifications
$arazzo = \Cdd\Arazzo\parse((string) file_get_contents('workflow.arazzo.json'));
\Cdd\Arazzo\emit($arazzo, './generated_workflows', ['subcommand' => 'to_sdk']);
CddCli::generate_to_arazzo(['-i', './generated_workflows', '-o', './extracted.arazzo.json']);

// 5. Generate documentation snippets JSON for an OpenAPI specification
CddCli::generate_docs_json(['--no-imports', '--no-wrapping', '-i', 'spec.json', '-o', 'docs.json']);
```

### Installation & Development

All `cdd-*` repositories support both native language build tools and a standardized cross-platform `Makefile` / `make.bat` interface (`deps`, `build`, `test`):

```sh
make deps
make build
make test
# or on Windows:
.\make.bat deps
.\make.bat build
.\make.bat test
```

#### Prerequisites

- **PHP 8.2+** with `ext-curl`, `ext-pdo`, `ext-pdo_sqlite`, and `phpdbg` (for oplog code coverage)
- **Composer** 2.x

#### Composer Installation & Standalone PHAR Build

Install `cdd-php` into an existing project or globally via Composer:

```sh
composer require offscale/cdd-php
# or globally:
composer global require offscale/cdd-php
```

To build the self-contained `bin/cdd-php` PHAR archive from source:

```sh
composer install
php -d phar.readonly=0 scripts/build_phar.php
```

#### Testing, Coverage, and Linting

```sh
# Run the test suite
composer test

# Verify 100% line and function coverage (via phpdbg oplog)
phpdbg -qrr bin/check_coverage.php

# Verify 100% PHPDoc documentation coverage (via nikic/php-parser)
php bin/check_docs.php

# Check PSR-12 / PHP-CS-Fixer formatting
vendor/bin/php-cs-fixer fix --dry-run --diff
```

> **Further Documentation**:
> - [**ARCHITECTURE.md**](ARCHITECTURE.md) — Compiler pipeline (Lexer → CST/DST/AST → Semantic Diff → Emitter).
> - [**COMPLIANCE.md**](COMPLIANCE.md) — Swagger 2.0, OpenAPI 3.2.0, Arazzo 1.1.0, and MCP support matrices.
> - [**USAGE.md**](USAGE.md) — Detailed CLI, SDK, ORM, and roundtrip usage guide.
> - [**DEVELOPING.md**](DEVELOPING.md) — Local development, linting, testing, and coverage guide.
> - [**WASM.md**](WASM.md) — WebAssembly / WASI build and browser/Node.js execution instructions.
> - [**PUBLISH.md**](PUBLISH.md) — Package publishing and release workflow.

---

## Universal CLI Toolchain

Once you learn the Compiler Driven Development CLI in one language, you can use the exact same commands, flags, and environment variables across the entire polyglot stack.

### Common Arguments & Environment Variables

| Flag | Short | Environment Variable | Description |
|---|---|---|---|
| `--help` | `-h` | — | Print help information for the CLI or subcommand. |
| `--version` | `-v` / `-V` | — | Print version information. |
| `--input <path>` | `-i` / `-f` | `CDD_INPUT` | Path or URL to the input OpenAPI/Arazzo spec or source file/directory. |
| `--input-dir <dir>` | `-d` | `CDD_INPUT_DIR` | Directory containing multiple OpenAPI/Arazzo specifications for batch generation. |
| `--output <path>` | `-o` | `CDD_OUTPUT` | Destination file or directory path for generated code or extracted specifications. |
| `--tests` | `-t` | `CDD_TESTS` | Generate composable contract/integration tests and API mocks alongside code. |
| `--mcp` | `-m` | `CDD_MCP` | Generate Model Context Protocol (MCP) server and client adapters. |
| `--no-github-actions` | — | `CDD_NO_GITHUB_ACTIONS` | Do not generate `.github/workflows/ci.yml` scaffolding in target output. |
| `--no-installable-package` | — | `CDD_NO_INSTALLABLE_PACKAGE` | Do not generate package manifest/build files in target output. |
| `--no-imports` | — | `CDD_NO_IMPORTS` | (`to_docs_json`) Omit import/include statements from generated documentation snippets. |
| `--no-wrapping` | — | `CDD_NO_WRAPPING` | (`to_docs_json`) Omit boilerplate wrapper functions/classes from documentation snippets. |
| `--port <port>` | `-p` | `CDD_PORT` | (`serve_json_rpc`) TCP port to listen on (default: `8080`). |
| `--listen <addr>` | `-l` | `CDD_LISTEN` | (`serve_json_rpc`) Host/IP address to bind to (default: `127.0.0.1`). |
| `--truth <source>` | `-t` | `CDD_TRUTH` | (`sync`) Designate the single source of truth when synchronizing. |

### Core Subcommands

- **`from_openapi [to_sdk | to_sdk_cli | to_server]`**: Compile an OpenAPI 3.2.0 or Swagger 2.0 specification into an idiomatic `PHP` Client SDK, Client CLI, or HTTP Server (patching existing files losslessly if the target directory already exists).
- **`to_openapi`**: Statically parse `PHP` models, routes, client calls, and docstrings into an authoritative OpenAPI 3.2.0 specification.
- **`from_arazzo [to_sdk | to_sdk_cli | to_server]`**: Compile an Arazzo 1.1.0 workflow specification into `PHP` workflow orchestrators, CLI workflow runners, or server/E2E workflow test suites.
- **`to_arazzo`**: Statically parse `PHP` workflow code, CLI scripts, or E2E tests into an Arazzo 1.1.0 specification.
- **`sync`**: Bidirectionally synchronize `PHP` source files (models, ORM entities, routes, functions, tests, and docstrings) with OpenAPI/Arazzo specifications in-place.
- **`to_docs_json`**: Generate documentation-optimized JSON containing `PHP` code snippets for each operation in an OpenAPI specification.
- **`serve_json_rpc`**: Expose the `cdd-php` compiler over a JSON-RPC 2.0 server for IDE integrations and external tooling.
- **`mcp`**: Run the `cdd-php` compiler as a Model Context Protocol (MCP) server over standard I/O (`stdio`).

### Verbatim CLI Help Output

#### Root CLI (`cdd-php --help`)

```text
cdd-php CLI
Usage:
  cdd-php [subcommand] [options]

Subcommands:
  from_openapi    Generate code from an OpenAPI specification.
  to_openapi      Generate an OpenAPI specification from source code.
  from_arazzo     Generate code from an Arazzo workflow specification.
  to_arazzo       Generate an Arazzo workflow specification from source code.
  to_docs_json    Generate JSON documentation with code snippets for an OpenAPI specification.
  serve_json_rpc  Expose CLI interface as a JSON-RPC server.
  sync            Synchronize an OpenAPI specification with source code.
  mcp             Run the Model Context Protocol (MCP) server over stdio.

Options:
  --help, -h      Show this help message
  --version, -v   Show version information

Examples:
  cdd-php serve_json_rpc [--port|-p 8080] [--listen|-l 127.0.0.1]
  cdd-php to_docs_json --no-imports --no-wrapping -i spec.json -o docs.json
  cdd-php from_openapi to_sdk_cli -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests] [--mcp]
  cdd-php from_openapi to_sdk -i spec.json -o target_directory [--no-github-actions] [--no-installable-package] [--tests] [--mcp]
  cdd-php from_openapi to_server -i spec.json -o target_directory
  cdd-php sync -i directory -t class
```

#### `from_openapi` (`cdd-php from_openapi --help`)

```text
Usage: cdd-php from_openapi <target> [options]

Targets:
  to_sdk_cli      Generate a client SDK and a corresponding CLI
  to_sdk          Generate a client SDK
  to_server       Generate server boilerplate, models, and routing logic

Options:
  -i, --input <spec>    Path to the OpenAPI specification file
  -d, --input-dir <dir> Path to a directory containing OpenAPI specifications
  -o, --output <dir>    Destination path for generation
  --no-github-actions   Disable GitHub Actions generation
  --no-installable-package Disable Composer package generation
  --tests               Generate PHPUnit tests
  --mcp                 Generate MCP server
  --help, -h            Show this help message
```

##### `from_openapi to_sdk` (`cdd-php from_openapi to_sdk --help`)

```text
Usage: cdd-php from_openapi to_sdk [options]

Generate a client SDK from an OpenAPI specification.

Options:
  -i, --input <spec>    Path to the OpenAPI specification file
  -d, --input-dir <dir> Path to a directory containing OpenAPI specifications
  -o, --output <dir>    Destination path for generation
  --no-github-actions   Disable GitHub Actions generation
  --no-installable-package Disable Composer package generation
  --tests               Generate PHPUnit tests
  --mcp                 Generate MCP server
  --help, -h            Show this help message
```

##### `from_openapi to_sdk_cli` (`cdd-php from_openapi to_sdk_cli --help`)

```text
Usage: cdd-php from_openapi to_sdk_cli [options]

Generate a client SDK and a corresponding CLI from an OpenAPI specification.

Options:
  -i, --input <spec>    Path to the OpenAPI specification file
  -d, --input-dir <dir> Path to a directory containing OpenAPI specifications
  -o, --output <dir>    Destination path for generation
  --no-github-actions   Disable GitHub Actions generation
  --no-installable-package Disable Composer package generation
  --tests               Generate PHPUnit tests
  --mcp                 Generate MCP server
  --help, -h            Show this help message
```

##### `from_openapi to_server` (`cdd-php from_openapi to_server --help`)

```text
Usage: cdd-php from_openapi to_server [options]

Generate server boilerplate, models, and routing logic from an OpenAPI specification.

Options:
  -i, --input <spec>    Path to the OpenAPI specification file
  -d, --input-dir <dir> Path to a directory containing OpenAPI specifications
  -o, --output <dir>    Destination path for generation
  --no-github-actions   Disable GitHub Actions generation
  --no-installable-package Disable Composer package generation
  --tests               Generate PHPUnit tests
  --mcp                 Generate MCP server
  --help, -h            Show this help message
```

#### `to_openapi` (`cdd-php to_openapi --help`)

```text
Usage: cdd-php to_openapi [options]

Options:
  -i, --input <path>    Path to the source code directory or file to parse
  -o, --output <path>   Destination file for the OpenAPI specification
  --help, -h            Show this help message
```

#### `to_docs_json` (`cdd-php to_docs_json --help`)

```text
Usage: cdd-php to_docs_json [options]

Options:
  -i, --input <spec>    Path to the OpenAPI specification file
  -o, --output <path>   Destination file for the JSON documentation
  --no-imports          Disable import statements in the generated documentation
  --no-wrapping         Disable line wrapping in the generated documentation
  --help, -h            Show this help message
```

#### `serve_json_rpc` (`cdd-php serve_json_rpc --help`)

```text
Usage: cdd-php serve_json_rpc [options]

Options:
  -p, --port <port>     Port to listen on (e.g., 8080)
  -l, --listen <addr>   Address to bind to (e.g., 127.0.0.1)
  --timeout <seconds>   Socket accept timeout in seconds
  --help, -h            Show this help message
```

---

## Special Features Beyond the Common Compiler Driven Development Subset

While all compilers in this suite implement the full shared baseline above, `cdd-php` implements additional capabilities tailored to the `PHP` ecosystem:

- **Multi-Tiered DAO Architecture (`StubDao`, `ConcreteDao`, `DaoFactory`)**:
  - Generated servers emit a complete Data Access Object layer (`{Schema}DaoInterface`, `Stub{Schema}Dao`, `Concrete{Schema}Dao`, and `DaoFactory`) alongside `Illuminate\Database\Eloquent\Model` classes. `DaoFactory::create()` inspects `DATABASE_URL` and runtime flags to switch between zero-dependency stubbed responses and live Eloquent ORM database operations without changing controller code.
- **Four Orthogonal Server Execution Modes (`ServerRunner.php` & Standalone `server.php`)**:
  - **Stub Mode** (`php src/ServerRunner.php` without `DATABASE_URL`): Routes requests through `StubDao` implementations without requiring any database connection.
  - **Production Mode** (`DATABASE_URL=postgres://user:pass@host:5432/db php src/ServerRunner.php`): Connects to PostgreSQL, MySQL, or SQLite via `Illuminate\Database\Capsule\Manager` and routes operations through `ConcreteDao` Eloquent models.
  - **Sandbox Mode** (`php src/ServerRunner.php --ephemeral`): Ignores `DATABASE_URL`, provisions an isolated `:memory:` SQLite database, and runs automatic schema migrations (`DatabaseConnection::migrate()`).
  - **Full Mock Mode** (`php src/ServerRunner.php --ephemeral --seed`): Boots an in-memory SQLite database and populates it with a topologically sorted graph of fake entities via `FakerPHP/Faker` (`DatabaseSeeder::seed()`), preserving foreign-key referential integrity across parent and child tables.
  - **Standalone Built-In Web Server Router (`server.php`)**: Emits a zero-config router (`php -S 127.0.0.1:8080 server.php`) that aliases `Illuminate\Support\Facades\Route` in-process, dispatches path parameters via `ReflectionMethod`, and automatically strips server base-path prefixes defined in `ApiServers`.
- **`nikic/php-parser` Format-Preserving AST/CST Patching & Static PHPDoc Extraction**:
  - Uses `nikic/php-parser` with `CloningVisitor` token attachment and `Standard::printFormatPreserving()` to patch existing classes, routes, controllers, and SDK methods in-place while preserving custom method bodies, comments, and whitespace.
  - Parses and emits structured PHPDoc tags (`@OAS-*`, `@param`, `@return`, `@throws`) and evaluates AST constant/array expressions (`\Cdd\Mocks\evaluateAstNode`) statically without `eval()` or executing untrusted user code.
- **Multi-Artifact `sync` with Schema Inference from Mocks**:
  - `cdd-php sync -i <dir> [-t class|spec]` synchronizes `openapi.json`, `arazzo.json`, `src/Models.php`, `src/routes.php`, `src/ApiController.php`, `src/ApiClient.php`, `src/api_cli.php`, `src/ApiServers.php`, `src/api_metadata.php`, `Webhooks.php`, `src/mocks.php`, `src/ComposableTests.php`, and `src/ApiWorkflows.php`, and automatically infers missing OpenAPI component schemas from mock response payloads (`\Cdd\Components\infer_schemas_from_mocks`).
- **Additional CLI Aliases, Flags, MCP Sampling, and Environment Variable Mapping**:
  - **`emit` Subcommand Alias**: `cdd-php emit` is supported as a direct alias for `cdd-php from_openapi`.
  - **Socket Timeout Flag (`--timeout`)**: `cdd-php serve_json_rpc --timeout <seconds>` (or `CDD_TIMEOUT`) configures the TCP socket accept timeout.
  - **Universal `CDD_*` Environment Variable Injection**: Any `CDD_<KEY>=<VALUE>` environment variable is automatically converted to `--<key> <value>` (lowercase, hyphenated) and appended to `$argv` if not already passed on the command line.
  - **Bidirectional MCP LLM Sampling (`CddCli::sample_llm`)**: Provides an MCP `sampling/createMessage` client helper over stdio alongside MCP server tools (`from_openapi`, `to_openapi`, `from_arazzo`, `to_arazzo`, `sync`, `generate_FromOpenApi`) and paginated resources (`cdd://ast`, `cdd://schema`).
- **Self-Contained PHAR & Custom-Section WebAssembly Bundle**:
  - Packages the compiler into both a standalone `build/cdd-php.phar` executable (`scripts/build_phar.php`) and a single-file `build/wasm/cdd-php.wasm` binary (`scripts/build_wasm_bundle.php` and `scripts/bundle_wasm_payload.php`) that embeds the entire PHP source tree inside a custom `"cdd_bundle"` WebAssembly section.

---

## The End of Specification Drift

With Compiler Driven Development, API specifications, source code, database schemas, documentation, and tests are no longer loosely coupled artifacts that drift apart over time. Because the compiler preserves every comment, docstring, and whitespace token via Concrete and Decorated Syntax Trees (CST/DST), your specifications and your implementations remain strict, lossless reflections of one another across all 13+ languages throughout the entire software lifecycle.

---

## License

Licensed under either of

- Apache License, Version 2.0 ([LICENSE-APACHE](LICENSE-APACHE) or <https://www.apache.org/licenses/LICENSE-2.0>)
- MIT license ([LICENSE-MIT](LICENSE-MIT) or <https://opensource.org/licenses/MIT>)

at your option.

### Contribution

Unless you explicitly state otherwise, any contribution intentionally submitted
for inclusion in the work by you, as defined in the Apache-2.0 license, shall be
dual licensed as above, without any additional terms or conditions.
