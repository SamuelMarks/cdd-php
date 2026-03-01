# Developing `cdd-php`

Thank you for your interest in contributing to `cdd-php`!

## Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/offscale/cdd-php.git
   cd cdd-php
   ```

2. **Install Dependencies:**
   Ensure you have PHP 8.1+ installed. Run Composer to fetch PHP-Parser and PHPUnit.
   ```bash
   make install_deps
   ```

3. **Install Git Hooks:**
   ```bash
   cp scripts/pre-commit.sh .git/hooks/pre-commit
   chmod +x .git/hooks/pre-commit
   ```

## Workflow

1. **Write code:** `src/` is where the parsers and emitters live. They are structured per OpenAPI domain (e.g., `src/classes`, `src/routes`).
2. **Write tests:** Add tests to `tests/`.
3. **Run tests:**
   ```bash
   make test
   ```
4. **Build CLI:**
   ```bash
   make build
   ```
   This generates the executable Phar archive at `bin/cdd-php.phar`.

## Architecture Note
Remember that this is a Compiler-Driven Development (CDD) project. Parsing PHP files must strictly use the AST (nikic/php-parser), never `Reflection` or runtime includes, ensuring safe processing of partial/uncompilable files.
