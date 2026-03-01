cdd-php
=======

[![License](https://img.shields.io/badge/license-Apache--2.0%20OR%20MIT-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![CI/CD](https://github.com/offscale/cdd-php/workflows/CI/badge.svg)](https://github.com/offscale/cdd-php/actions)
![Test Coverage](https://img.shields.io/badge/Test%20Coverage-100%25-brightgreen.svg)
![Doc Coverage](https://img.shields.io/badge/Doc%20Coverage-100%25-brightgreen.svg)

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

Requires PHP 8.1+ and Composer.

Install directly via Composer (once published):
```bash
composer require offscale/cdd-php
```

Or build locally:
```bash
git clone https://github.com/offscale/cdd-php.git
cd cdd-php
make install_deps
make build
```

## 🛠 Usage

### Command Line Interface

Generate PHP models and routing from an OpenAPI specification:
```bash
php bin/cdd-php from_openapi -i openapi.json
```

Generate an OpenAPI specification from existing PHP source code:
```bash
php bin/cdd-php to_openapi -f src/Models.php > openapi.json
```

Synchronize everything (docs, code, and mocks):
```bash
php bin/cdd-php sync -d out
```

### Programmatic SDK / Library

```php
require_once 'vendor/autoload.php';

// Parse OpenAPI JSON to intermediate spec
$spec = \Cdd\Openapi\parse(file_get_contents('openapi.json'));

// Emit PHP code to a target directory
\Cdd\Openapi\emit($spec, 'out');
```

## Design choices

This project relies purely on native PHP abstract syntax trees (AST) through libraries like `nikic/php-parser`. This ensures comment and whitespace sensitivity, allowing it to safely read and rewrite complex, incomplete codefiles without losing docblocks, metadata, or formatting. It achieves Bidirectional sync by merging changes dynamically and emitting native code via its respective emit modules. It maintains zero dependencies for its generated code to keep it extremely lightweight and portable.

## 🏗 Supported Conversions for PHP

*(The boxes below reflect the features supported by this specific `cdd-php` implementation)*

| Concept | Parse (From) | Emit (To) |
|---------|--------------|-----------|
| OpenAPI (JSON/YAML) | [✅] | [✅] |
| `PHP` Models / Structs / Types | [✅] | [✅] |
| `PHP` Server Routes / Endpoints | [✅] | [✅] |
| `PHP` API Clients / SDKs | [✅] | [✅] |
| `PHP` ORM / DB Schemas | [ ] | [ ] |
| `PHP` CLI Argument Parsers | [ ] | [ ] |
| `PHP` Docstrings / Comments | [✅] | [✅] |
| WASM Build Support | [ ] | [ ] |

WASM support is theoretically possible using projects like `php-wasm` or Emscripten, but is not yet fully implemented due to the complexity of embedding the PHP interpreter alongside the source code. See `WASM.md` for more details.

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
