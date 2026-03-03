cdd-php
============

[![License](https://img.shields.io/badge/license-Apache--2.0%20OR%20MIT-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![CI/CD](https://github.com/offscale/cdd-php/workflows/CI/badge.svg)](https://github.com/offscale/cdd-php/actions)
[![Doc Coverage](https://img.shields.io/badge/doc_coverage-100%25-brightgreen.svg)](https://github.com/offscale/cdd-php)
[![Test Coverage](https://img.shields.io/badge/test_coverage-100%25-brightgreen.svg)](https://github.com/offscale/cdd-php)

OpenAPI ↔ PHP. This is one compiler in a suite, all focussed on the same task: Compiler Driven Development (CDD).

Each compiler is written in its target language, is whitespace and comment sensitive, and has both an SDK and CLI.

The CLI—at a minimum—has:
- `cdd-php --help`
- `cdd-php --version`
- `cdd-php from_openapi -i spec.json`
- `cdd-php to_openapi -f path/to/code`
- `cdd-php to_docs_json --no-imports --no-wrapping -i spec.json`

The goal of this project is to enable rapid application development without tradeoffs. Tradeoffs of Protocol Buffers / Thrift etc. are an untouchable "generated" directory and package, compile-time and/or runtime overhead. Tradeoffs of Java or JavaScript for everything are: overhead in hardware access, offline mode, ML inefficiency, and more. And neither of these alterantive approaches are truly integrated into your target system, test frameworks, and bigger abstractions you build in your app. Tradeoffs in CDD are code duplication (but CDD handles the synchronisation for you).

## 🚀 Capabilities

The `cdd-php` compiler leverages a unified architecture to support various facets of API and code lifecycle management.

* **Compilation**:
  * **OpenAPI → `PHP`**: Generate idiomatic native models, network routes, client SDKs, database schemas, and boilerplate directly from OpenAPI (`.json` / `.yaml`) specifications.
  * **`PHP` → OpenAPI**: Statically parse existing `PHP` source code and emit compliant OpenAPI specifications.
* **AST-Driven & Safe**: Employs static analysis (Abstract Syntax Trees) instead of unsafe dynamic execution or reflection, allowing it to safely parse and emit code even for incomplete or un-compilable project states.
* **Seamless Sync**: Keep your docs, tests, database, clients, and routing in perfect harmony. Update your code, and generate the docs; or update the docs, and generate the code.

## 📦 Installation

This project is a CLI library without external dependencies other than `nikic/php-parser` for reading ASTs.

Install globally or locally via Composer:
```bash
composer require offscale/cdd-php
```

Or download the prebuilt CLI executable from GitHub releases.

## 🛠 Usage

### Command Line Interface

```bash
# Convert a PHP file to OpenAPI 3.2.0 spec
php bin/cdd-php to_openapi -f path/to/code.php -o spec.json

# Generate SDK from an OpenAPI spec
php bin/cdd-php from_openapi to_sdk -i spec.json -o src/Client

# Generate JSON-RPC server and start serving
php bin/cdd-php serve_json_rpc --port 8082 --listen 0.0.0.0
```

### Programmatic SDK / Library

```php
<?php

require 'vendor/autoload.php';

use Cdd\Openapi\parse;
use Cdd\Openapi\emit;

$spec = parse(file_get_contents('spec.json'));
$phpCode = emit($spec, 'src/');
```

## Design choices

To ensure 100% test coverage and full AST parsing fidelity, this project relies on `nikic/php-parser`. Using the standard AST parser for PHP provides bulletproof parsing even for complex classes. We specifically targeted generating zero-dependency output files so consumers of the generated SDK do not need large external dependencies. This project offers symmetric parsing and emitting of the `cdd` Intermediate Representation.

## 🏗 Supported Conversions for PHP

*(The boxes below reflect the features supported by this specific `cdd-php` implementation)*

| Concept | Parse (From) | Emit (To) |
|---------|--------------|-----------|
| OpenAPI (JSON/YAML) | ✅ | ✅ |
| `PHP` Models / Structs / Types | ✅ | ✅ |
| `PHP` Server Routes / Endpoints | ✅ | ✅ |
| `PHP` API Clients / SDKs | ✅ | ✅ |
| `PHP` ORM / DB Schemas | ✅ | ✅ |
| `PHP` CLI Argument Parsers | ✅ | ✅ |
| `PHP` Docstrings / Comments | ✅ | ✅ |

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
