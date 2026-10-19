# Compliance

`cdd-php` implements full bidirectional compilation and conformance across the following specifications and ecosystems:

## Specification Compliance

* **OpenAPI 3.2.0 / 3.1.x / 3.0.x**: 100% parser and emitter compliance, including `$self`, `jsonSchemaDialect`, `webhooks`, `additionalOperations`, `QUERY` HTTP method, `querystring` parameter location, `itemSchema` streaming media types, OAuth2 `deviceAuthorization` flows, and hierarchical tags (`summary`, `parent`, `kind`).
* **Swagger 2.0**: Automatic lossless upgrade (`\Cdd\Openapi\upgrade_swagger2_to_openapi3`) on input and downgrade (`\Cdd\Openapi\downgradeOpenApi3ToSwagger2`) when targeting Swagger 2.0 (`target_version => '2.0'`).
* **Arazzo 1.1.0 & 1.0.x**: Full workflow orchestration compilation and extraction (`\Cdd\Arazzo\parse`, `\Cdd\Arazzo\emit`, `\Cdd\Arazzo\downgradeArazzo11To10`), runtime expression evaluation (`\Cdd\Arazzo\evaluateRuntimeExpression`), and criterion evaluation (`\Cdd\Arazzo\evaluateCriterion` supporting simple, regex, JSONPath, and XPath conditions).
* **Model Context Protocol (MCP)**: Native MCP server over stdio (`cdd-php mcp`, protocol version `2024-11-05`), bidirectional LLM sampling (`\Cdd\Cli\CddCli::sample_llm`), and MCP tool schema generation (`--mcp`, `\Cdd\Openapi\emit_mcp_LockServer`, `\Cdd\Openapi\parse_mcp_to_paths`).

Detailed per-target conformance checklists are maintained in [`conformance/`](conformance/):
- [`conformance/openapi-3.2.0/`](conformance/openapi-3.2.0/)
- [`conformance/swagger-2.0/`](conformance/swagger-2.0/)
- [`conformance/arazzo-1.1.0/`](conformance/arazzo-1.1.0/)
- [`conformance/mcp-1.0.0/`](conformance/mcp-1.0.0/)

## Language & Framework Standards

* **PHP**: Strict types (`declare(strict_types=1);`), PSR-4 autoloading, and PSR-12 / PHP-CS-Fixer coding standard compatibility. Minimum runtime version is PHP 8.2+.
* **AST/CST & Docblocks**: Static AST/CST parsing and format-preserving emission powered by `nikic/php-parser` with 100% PHPDoc coverage (`@package`, `@param`, `@return`, `@throws`, `@OAS-*`).
* **Generated Target Frameworks**:
  * **Server & ORM**: Laravel-style routing (`Illuminate\Support\Facades\Route`), Eloquent ORM models and migrations (`illuminate/database`), multi-tiered DAOs (`StubDao` / `ConcreteDao`), `FakerPHP/Faker` seeders, and a zero-config standalone server router (`server.php`).
  * **Client SDK**: Native `ext-curl` HTTP client (`ApiClient`) with typed parameter validation, security header/query/cookie injection, and multipart/form-encoding support.
  * **CLI**: Symfony Console (`symfony/console`) commands (`api_cli.php`, `arazzo_cli.php`).

## Support Matrix

| Specification Concept                         | Status |
| --------------------------------------------- | ------ |
| OpenAPI 3.2.0 Root (`$self`, Dialect, Info)   | ✅     |
| Swagger 2.0 Upgrade & Downgrade               | ✅     |
| Server Object & Server Variables              | ✅     |
| Components Object (All 10 Registry Sections)  | ✅     |
| Paths, Webhooks & Path Item Objects           | ✅     |
| Operation Object & `additionalOperations`     | ✅     |
| Parameter Object (`path`, `query`, `querystring`, `header`, `cookie`) | ✅ |
| Request Body, Encoding & Media Type (`itemSchema`) | ✅ |
| Responses, Headers & Links                    | ✅     |
| JSON Schema Draft 2020-12 & Discriminator     | ✅     |
| Reference Object (`$ref`, `$dynamicRef`)      | ✅     |
| Security Schemes & OAuth2 (`deviceAuthorization`) | ✅ |
| Callbacks & Hierarchical Tags                 | ✅     |
| Arazzo 1.1.0 Workflows, Steps & Expressions   | ✅     |
| Model Context Protocol (MCP) Tools & Sampling | ✅     |
