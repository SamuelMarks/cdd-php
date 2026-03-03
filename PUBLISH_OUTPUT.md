# Publishing Generated Client Libraries

The primary goal of `cdd-php` is to generate API client SDKs (and Servers) natively matching your architecture.

## Workflow

To keep your generated PHP SDK automatically updated alongside changes in the OpenAPI Specification:

1. Create a `cronjob` via GitHub Actions in the project where you manage your OpenAPI specifications.
2. In the Action, check out the target SDK repository.
3. Run `cdd-php from_openapi to_sdk -i openapi.json -o src/`.
4. Run `composer test` or similar checks.
5. Create a Pull Request (or push directly) to the target repository.

This guarantees your SDK is continuously synchronized with the upstream API contract without requiring a manual update.
