# Developing `cdd-php`

To contribute to `cdd-php`, ensure you have **PHP 8.2+** (including `phpdbg`, `ext-curl`, `ext-pdo`, and `ext-pdo_sqlite`) and **Composer 2.x** installed.

## Setup

```bash
make deps
# or:
make install_deps
```

## Running Tests & Quality Gates

`cdd-php` enforces **100% executable line coverage** (with zero coverage-ignore annotations) and **100% PHPDoc documentation coverage**.

```bash
# Run the test suite via the custom runner
make test
# or directly:
php tests/test_runner.php

# Verify 100% line coverage with phpdbg oplog
phpdbg -qrr bin/check_coverage.php

# Verify 100% PHPDoc coverage across src/, bin/, scripts/, and tests/
php bin/check_docs.php

# Check code formatting with PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Repository Structure

* `src/`: Modular parsers (`parse.php`) and emitters (`emit.php`) organized by domain (`arazzo/`, `arazzo_cli/`, `arazzo_client/`, `arazzo_server/`, `classes/`, `cli/`, `client/`, `components/`, `controllers/`, `daos/`, `database/`, `docstrings/`, `encoding/`, `functions/`, `info/`, `mocks/`, `openapi/`, `operations/`, `parameters/`, `paths/`, `readme/`, `request_bodies/`, `responses/`, `routes/`, `schemas/`, `security/`, `seeder/`, `server_cli/`, `servers/`, `standalone_server/`, `tests/`, `webhooks/`).
* `tests/`: Unit, integration, and roundtrip test suites mirroring `src/`, executed by `tests/test_runner.php` and `tests/framework/Runner.php`.
* `bin/cdd-php`: Main CLI entrypoint.
* `bin/check_coverage.php`: `phpdbg` oplog coverage verifier (enforces 100% coverage and rejects `@codeCoverageIgnore` / `cov_ignore`).
* `bin/check_docs.php`: AST-based PHPDoc coverage verifier (enforces file, class, property, constant, and function/method docblocks with `@package`, `@param`, `@return`, and `@throws`).
* `scripts/`: Build and packaging scripts (`build_phar.php`, `build_wasm_bundle.php`, `bundle_wasm_payload.php`, `update_badges.php`, `start_mock_server.sh`).

## Adding a Feature

1. Identify the OpenAPI 3.2.0, Swagger 2.0, Arazzo 1.1.0, MCP, or PHP AST construct.
2. Update the corresponding `parse.php` and/or `emit.php` module inside `src/`.
3. Add comprehensive tests in `tests/`.
4. Verify `phpdbg -qrr bin/check_coverage.php`, `php bin/check_docs.php`, and `vendor/bin/php-cs-fixer fix --dry-run --diff` all pass at 100%.
