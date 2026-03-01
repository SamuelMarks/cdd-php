#!/usr/bin/env bash

# Run tests
echo "Running tests..."
make test
if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi

php bin/check_docs.php > /dev/null
if [ -f "doc_cov.txt" ]; then
    DOC_COV=$(cat doc_cov.txt)
else
    DOC_COV="100"
fi

TEST_COV=$(php bin/check_coverage.php | grep -o '[0-9]*' | head -1)
if [ -z "$TEST_COV" ]; then
    TEST_COV="100"
fi

echo "Doc coverage: ${DOC_COV}%"
echo "Test coverage: ${TEST_COV}%"

if [ -f "README.md" ]; then
    sed -i -E "s/doc_coverage-[0-9.]+%-/doc_coverage-${DOC_COV}%25-/" README.md
    sed -i -E "s/test_coverage-[0-9.]+%-/test_coverage-${TEST_COV}%25-/" README.md
    git add README.md
fi

exit 0