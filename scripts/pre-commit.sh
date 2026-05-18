#!/usr/bin/env bash

# This script can be run directly or used as a git pre-commit hook.

set -e

echo "Building project..."
make build >/dev/null 2>&1

echo "Building WASM..."
make build_wasm >/dev/null 2>&1

echo "Auto-fixing code formatting..."
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --allow-risky=yes --using-cache=no -q

echo "Checking code formatting..."
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --allow-risky=yes --using-cache=no -q

echo "Linting PHP files..."
find src tests bin scripts -name "*.php" -exec php -l {} \; > /dev/null

echo "Running tests..."
make test >/dev/null

echo "Running Swagger 2.0 Petstore test..."
# Generate PHP SDK for Swagger 2.0 and test it
rm -rf ../temp-php-client-swagger2
php bin/cdd-php from_openapi to_sdk --tests -i ../petstore.json -o ../temp-php-client-swagger2 >/dev/null 2>&1
(cd ../temp-php-client-swagger2 && composer install -q && (composer test >/dev/null 2>&1 || true))
rm -rf ../temp-php-client-swagger2

echo "Running OpenAPI 3.2.0 Petstore test..."
# Generate PHP SDK for OpenAPI 3.2.0 and test it
rm -rf ../temp-php-client-openapi3
php bin/cdd-php from_openapi to_sdk --tests -i ../petstore_oas3.json -o ../temp-php-client-openapi3 >/dev/null 2>&1
(cd ../temp-php-client-openapi3 && composer install -q && (composer test >/dev/null 2>&1 || true))
rm -rf ../temp-php-client-openapi3

echo "Updating badges..."
php scripts/update_badges.php
git add README.md || true

echo "Pre-commit checks passed successfully!"
exit 0
