cdd-php
=======
[![License](https://img.shields.io/badge/license-Apache--2.0%20OR%20MIT-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![CI](https://github.com/SamuelMarks/cdd-php/actions/workflows/ci.yml/badge.svg)](https://github.com/SamuelMarks/cdd-php/actions)
[![Test Coverage](https://img.shields.io/badge/test_coverage-100%25-brightgreen.svg)](#)
[![Doc Coverage](https://img.shields.io/badge/doc_coverage-100%25-brightgreen.svg)](#)

----

OpenAPI ↔ PHP. This is one compiler in a suite, all focussed on the same task: Compiler Driven Development (CDD).

Each compiler is written in its target language, is whitespace and comment sensitive, and has both an SDK and CLI.

The core philosophy of Compiler Driven Development (CDD) is synchronization without compromise. Where traditional generators silo your API boundaries into read-only files, this compiler natively merges changes into your codebase via a robust, [whitespace and comment aware] Abstract Syntax Tree (AST) driven parser & emitter. It bridges the gap between design and implementation, allowing you to seamlessly generate SDKs from a spec or extract a spec from existing code. By keeping your APIs, SDKs, and tests in continuous, automated alignment, it drastically improves both delivery speed and software reliability.

The CLI—at a minimum—has:

- `cdd-php --help`
- `cdd-php --version`
- `cdd-php from_openapi to_sdk_cli -i spec.json`
- `cdd-php from_openapi to_sdk -i spec.json`
- `cdd-php from_openapi to_server -i spec.json`
- `cdd-php to_openapi -f path/to/code`
- `cdd-php to_docs_json --no-imports --no-wrapping -i spec.json`
- `cdd-php serve_json_rpc --port 8080 --listen 0.0.0.0`

## SDK Example

```php
<?php
require 'vendor/autoload.php';

use Cdd\CddGenerator;
use Cdd\Config;

$config = new Config('spec.json', 'src/models');
CddGenerator::generateSdk($config);
echo "SDK generation complete.\n";
?>
```

## Installation

```bash
composer install
```

## Development

You can use standard tooling commands or the included cross-platform Makefiles to fetch dependencies, build, and test:

```bash
composer install
./vendor/bin/phpunit
# or
make deps
make build
make test
# or on Windows
.\make.bat deps
.\make.bat build
.\make.bat test
```

See [PUBLISH.md](PUBLISH.md) for packaging and releasing.

## Features

The `cdd-php` compiler leverages a unified architecture to support various facets of API and code lifecycle management. For a deep dive into the compiler's design, see [ARCHITECTURE.md](ARCHITECTURE.md).

- **Compilation**:
    - **OpenAPI → `PHP`**: Generate idiomatic native models, network routes, client SDKs, and boilerplate directly from OpenAPI (`.json` / `.yaml`) specifications.
    - **`PHP` → OpenAPI**: Statically parse existing `PHP` source code and emit compliant OpenAPI specifications.
- **AST-Driven & Safe**: Employs static analysis instead of unsafe dynamic execution or reflection, allowing it to safely parse and emit code even for incomplete or un-compilable project states.
- **Seamless Sync**: Keep your docs, tests, database, clients, and routing in perfect harmony. Update your code, and generate the docs; or update the docs, and generate the code.

**Uncommon Features:**

`cdd-php` supports standard CDD features.

## CLI Options

```text
Usage: cdd-php [command] [args]
Commands:
  --help
  --version
  to_openapi -f path/to/code -o spec.json
  serve_json_rpc --port 8082 --listen 0.0.0.0
  to_docs_json --no-imports --no-wrapping -i spec.json -o docs.json
  from_openapi to_sdk_cli -i spec.json -o target_directory
  from_openapi to_sdk_cli --input-dir ./specs/ -o target_directory
  from_openapi to_sdk -i spec.json -o target_directory
  from_openapi to_sdk --input-dir ./specs/ -o target_directory
  from_openapi to_server -i spec.json -o target_directory
  from_openapi to_server --input-dir ./specs/ -o target_directory

Options for from_openapi:
  --no-github-actions
  --no-installable-package
  --tests

Additional Commands:
  sync -d directory
  test
```

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
