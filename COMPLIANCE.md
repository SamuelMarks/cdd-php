# Compliance

`cdd-php` currently supports the following standards and frameworks:

## OpenAPI Compliance

*   **Version Target:** OpenAPI Specification 3.2.0 (Draft/Main)
*   **Current Status:** Parsers and Emitters correctly handle the majority of OpenAPI features. Complete compliance with all nuanced validation logic and `$ref` resolution is an ongoing effort.
*   **Target Coverage:** 100% features supported in 3.2.0 version.

## Language Standards

*   **PHP:** PSR-4 autoloading, PSR-12 coding standard compatibility. Minimum version is PHP 8.2+.
*   **Docblocks:** Follows standard PHPDoc conventions for type hinting and parameter extraction.

## Frameworks

*   By default, `cdd-php` can parse and emit generic PSR-7/PSR-15 style HTTP routes and controllers.
*   It supports integrating with popular routing mechanisms and ORMs via AST analysis without needing explicit runtime inclusion.
