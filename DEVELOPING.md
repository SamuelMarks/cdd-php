# Developing cdd-php

To contribute to `cdd-php`, make sure you have PHP 8.2 and Composer installed.

## Setup

```bash
make install_deps
```

## Running Tests

All parsing and emitting functionality is covered by PHPUnit (or the custom test framework in `tests/framework/Runner.php`).

```bash
make test
```

## Structure

*   `src/`: Contains the AST parsers (`parse.php`) and emitters (`emit.php`) for each modular section (functions, classes, components, operations, etc.).
*   `tests/`: Test suites corresponding to the `src/` modular structure.
*   `bin/cdd-php`: The main entrypoint.
*   `scripts/`: Useful scripts such as `build_phar.php` and `pre-commit.sh`.

## Adding a Feature

1. Identify the OpenAPI element or PHP construct.
2. Update the corresponding `parse.php` or `emit.php` inside the respective folder in `src/`.
3. Add a test case in `tests/`.
4. Ensure `make test` passes with 100% coverage.
