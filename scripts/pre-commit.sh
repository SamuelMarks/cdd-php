#!/usr/bin/env bash

# Run tests
make test
if [ $? -ne 0 ]; then
  echo "Tests failed. Commit aborted."
  exit 1
fi

# Calculate Coverage
TEST_COV=$(php bin/check_coverage.php | tail -n 1)
DOC_COV=$(php bin/check_docs.php | grep "Doc Coverage:" | awk '{print $3}' | tr -d '%')

# Update README badges
sed -i -E "s|Test%20Coverage-[0-9]+%25|Test%20Coverage-${TEST_COV}%25|g" README.md || true
sed -i -E "s|Doc%20Coverage-[0-9]+%25|Doc%20Coverage-${DOC_COV}%25|g" README.md || true

echo "Pre-commit checks passed."
exit 0
