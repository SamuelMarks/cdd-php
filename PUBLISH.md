# Publishing `cdd-php`

## Composer Package (`Packagist`)

The `cdd-php` compiler is distributed as the Composer package `offscale/cdd-php`.
To publish a new release:

1. Update the `version` field in `composer.json` and the `--version` string in `src/cli/CddCli.php` (currently `0.0.4`).
2. Run the test, coverage, and documentation checks:
   ```bash
   phpdbg -qrr bin/check_coverage.php
   php bin/check_docs.php
   ```
3. Commit the changes and create a version tag (e.g., `v0.0.4`) on GitHub.

Packagist synchronizes releases from GitHub tags, allowing users to install `cdd-php` via:

```bash
composer require offscale/cdd-php
```

## Standalone PHAR & WASM Artifacts

To build the standalone PHAR executable and WebAssembly bundle for release assets:

```bash
make build
make build_wasm
```

## Publishing API Docs (`docs.json`)

To generate the documentation snippet bundle:

1. Run the `build_docs` target:
   ```bash
   make build_docs
   ```
2. The generated `docs.json` artifact is written to `docs/docs.json` (or `$(DOCS_DIR)/docs.json`).
