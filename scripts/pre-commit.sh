#!/usr/bin/env bash

# This script can be run directly or used as a git pre-commit hook.

set -e

echo "Building project..."
make build >/dev/null 2>&1

echo "Auto-fixing code formatting..."
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --allow-risky=yes --using-cache=no -q

echo "Checking code formatting..."
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --allow-risky=yes --using-cache=no -q

echo "Linting PHP files..."
for file in $(find src tests bin scripts -name "*.php"); do
    php -l "$file" > /dev/null || exit 1
done

echo "Running tests..."
make test >/dev/null

echo "Updating badges..."
python3 scripts/update_badges.py
git add README.md

echo "Pre-commit checks passed successfully!"
exit 0
