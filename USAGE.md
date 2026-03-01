# Usage

`cdd-php` can be used directly as a CLI or imported as a PHP library.

## CLI Options

```bash
# Show help
php bin/cdd-php --help

# Generate PHP from OpenAPI
php bin/cdd-php from_openapi -i spec.json

# Parse PHP to OpenAPI
php bin/cdd-php to_openapi -f path/to/Models.php

# Export docs JSON snippet for documentation generators
php bin/cdd-php to_docs_json --no-imports --no-wrapping -i spec.json
```

## Makefile

We provide standard `Makefile` / `make.bat` wrappers.

```bash
make install_base   # Install runtime
make install_deps   # Composer install
make build_docs     # Build coverage and docs
make build          # Create bin/cdd-php.phar
make test           # Run tests
make run --help     # Run the compiled binary
```

## Library Access

You can include the parser/emitter logic in your own build scripts without invoking the CLI format. Ensure your Composer autoloader is active.

```php
use Cdd\Openapi;

// Parse OpenAPI
$openapiArray = Openapi\parse(file_get_contents('openapi.json'));

// Generate PHP to a directory
Openapi\emit($openapiArray, 'output_directory');
```
