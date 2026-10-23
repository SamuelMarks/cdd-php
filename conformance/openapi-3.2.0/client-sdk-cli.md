# OpenAPI 3.2.0 Conformance Table: Client SDK CLI (CLI Tooling & Tests)

This table tracks the completeness of language integration with OpenAPI 3.2.0 for generating Command-Line Interfaces (CLIs) wrapper tools, and vice-versa.

### Legend & Tracking Guide
*   **To**: Language -> OpenAPI (Generating the OpenAPI document from declarative CLI structures)
*   **From**: OpenAPI -> Language (Generating CLI routing, flag parsing, and formatting from OpenAPI)
*   **Presence `[To, From]`**: The object is successfully parsed, validated, utilized, or generated.
*   **Absence `[To, From]`**: The object is currently unsupported, dropped, or falls back to generic/`any` types.
*   **Skipped `[To, From]`**: Intentionally ignored because it is irrelevant or unsupported by the CLI environment.
*   **Checkboxes**: Mark `[x]` as conformance is achieved.

| OpenAPI 3.2.0 Object / Feature | Presence `[To, From]` | Absence `[To, From]` | Skipped `[To, From]` | Notes / Implementation Strategy |
| :--- | :---: | :---: | :---: | :--- |
| **OpenAPI Object (Root)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Root CLI definition |
| **OpenAPI Object (`openapi`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Openapi\parse` and `Cdd\Openapi\emit` (`3.2.0`) |
| **OpenAPI Object (`$self`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Base URI resolution for internal and external references |
| **OpenAPI Object (`info`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse`, `Cdd\Info\emit`, and `api_metadata.php` |
| **OpenAPI Object (`jsonSchemaDialect`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` and `Cdd\Openapi\parse` |
| **OpenAPI Object (`servers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` (`ApiServers.php`) |
| **OpenAPI Object (`paths`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **OpenAPI Object (`webhooks`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Webhooks\parse` and `Cdd\Webhooks\emit` (`Webhooks.php`) |
| **OpenAPI Object (`components`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` (`Models.php`) |
| **OpenAPI Object (`security`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse`, `Cdd\Security\emit`, and `api_metadata.php` |
| **OpenAPI Object (`tags`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` and `Cdd\Openapi\parse` |
| **OpenAPI Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` and `@see` PHPDoc annotations |
| **Info Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | CLI --help text, --version command |
| **Info Object (`title`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@title`) |
| **Info Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Short summary for CLI global help text |
| **Info Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **Info Object (`termsOfService`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@termsOfService`) |
| **Info Object (`contact`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@contact`) |
| **Info Object (`license`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@license`) |
| **Info Object (`version`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@version`) |
| **Contact Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **Contact Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@contact` PHPDoc and `api_metadata.php` |
| **Contact Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@contact` PHPDoc and `api_metadata.php` |
| **Contact Object (`email`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@contact` PHPDoc and `api_metadata.php` |
| **License Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **License Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@license` PHPDoc and `api_metadata.php` |
| **License Object (`identifier`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | SPDX license identifier extraction |
| **License Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@license` PHPDoc and `api_metadata.php` |
| **Server Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Global --server or --host flag mapping |
| **Server Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` |
| **Server Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` |
| **Server Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Unique name used as CLI alias for a host environment |
| **Server Object (`variables`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` |
| **Server Variable Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Individual host template CLI flags |
| **Server Variable Object (`enum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` |
| **Server Variable Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` |
| **Server Variable Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Servers\parse` and `Cdd\Servers\emit` |
| **Components Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Reusable flag groups or interactive prompt states |
| **Components Object (`schemas`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Components Object (`responses`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` |
| **Components Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` |
| **Components Object (`examples`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Components Object (`requestBodies`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` |
| **Components Object (`headers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` |
| **Components Object (`securitySchemes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Components Object (`links`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` |
| **Components Object (`callbacks`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components\parse` and `Cdd\Components\emit` |
| **Components Object (`pathItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Reusable subcommand groupings |
| **Components Object (`mediaTypes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Reusable payload flag definitions |
| **Paths Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Structural mapping to CLI namespaces |
| **Paths Object (`/{path}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Subcommand grouping |
| **Path Item Object (`$ref`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Resolved & preserved via `Cdd\Openapi\resolve_refs` and `Cdd\Paths` |
| **Path Item Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths\parse` and `Cdd\Paths\emit` |
| **Path Item Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths\parse` and `Cdd\Paths\emit` |
| **Path Item Object (`get`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`put`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`post`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`delete`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`options`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`head`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`patch`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`trace`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`query`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | QUERY HTTP method subcommand |
| **Path Item Object (`additionalOperations`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Map of custom HTTP methods to subcommands |
| **Path Item Object (`servers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths\parse` and `Cdd\Paths\emit` |
| **Path Item Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths\parse` and `Cdd\Paths\emit` |
| **Operation Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | The execution targets of subcommands (e.g., cli users get) |
| **Operation Object (`tags`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@tag`) |
| **Operation Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` |
| **Operation Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` |
| **Operation Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@see`) |
| **Operation Object (`operationId`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to PHP method/command names in `Cdd\Operations`, `Cdd\Client`, and `Cdd\Cli` |
| **Operation Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Parameters`, `Cdd\Operations`, `Cdd\Client`, and `Cdd\Cli` |
| **Operation Object (`requestBody`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies`, `Cdd\Operations`, and `Cdd\Client` |
| **Operation Object (`responses`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses`, `Cdd\Operations`, `Cdd\Client`, and `Cdd\Cli` |
| **Operation Object (`callbacks`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@callback`) |
| **Operation Object (`deprecated`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@deprecated`) |
| **Operation Object (`security`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@security`) |
| **Operation Object (`servers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@server`) |
| **External Documentation Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Added to See also: in subcommand help |
| **External Documentation Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `@see` PHPDoc annotations in `Cdd\Docstrings` |
| **External Documentation Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `@see` PHPDoc annotations in `Cdd\Docstrings` |
| **Parameter Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Parameters\parse` and `Cdd\Parameters\emit` |
| **Parameter Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to PHP parameter/flag name in `Cdd\Parameters`, `Cdd\Client`, and `Cdd\Cli` |
| **Parameter Object (`in`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Handled for `path`, `query`, `header`, and `cookie` in `Cdd\Parameters` and `Cdd\Client` |
| **Parameter Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted in `@param` / `@oas-param` PHPDoc annotations |
| **Parameter Object (`required`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to non-nullable/required parameter metadata and validation |
| **Parameter Object (`deprecated`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `[deprecated]` / `@oas-param` parameter metadata |
| **Parameter Object (`allowEmptyValue`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `[allowEmptyValue]` / `@oas-param` parameter metadata |
| **Parameter Object (`example`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Parameters` and `@oas-param` metadata |
| **Parameter Object (`examples`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Parameters` and `@oas-param` metadata |
| **Parameter Object (`style`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Serialized & parsed via `Cdd\Parameters`, `Cdd\Client`, and `@oas-param` |
| **Parameter Object (`explode`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Serialized & parsed via `Cdd\Parameters`, `Cdd\Client`, and `@oas-param` |
| **Parameter Object (`allowReserved`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Serialized with `rawurlencode` bypass & parsed via `Cdd\Parameters` |
| **Parameter Object (`schema`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to PHP type hints and `@param` / `@oas-param` schema definitions |
| **Parameter Object (`content`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Parameters\parse` and `Cdd\Parameters\emit` |
| **Request Body Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Handled via file ingest (-F @data.json), STDIN pipe, or nested flags |
| **Request Body Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies\parse` and `Cdd\RequestBodies\emit` |
| **Request Body Object (`content`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies\parse` and `Cdd\RequestBodies\emit` |
| **Request Body Object (`required`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies\parse` and `Cdd\RequestBodies\emit` |
| **Media Type Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Inferred based on payload flag logic |
| **Media Type Object (`schema`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies` and `Cdd\Responses` |
| **Media Type Object (`itemSchema`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Validation for individual items in a CLI array flag |
| **Media Type Object (`example`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies` and `Cdd\Responses` |
| **Media Type Object (`examples`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\RequestBodies` and `Cdd\Responses` |
| **Media Type Object (`encoding`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Media Type Object (`prefixEncoding`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Media Type Object (`itemEncoding`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Internal CLI form-data builder logic |
| **Encoding Object (`contentType`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`headers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`encoding`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`prefixEncoding`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`itemEncoding`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`style`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`explode`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Encoding Object (`allowReserved`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Encoding\parse` and `Cdd\Encoding\emit` |
| **Responses Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Determines Exit Codes (0 vs 1, etc.) |
| **Responses Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses\parse` and `Cdd\Responses\emit` |
| **Responses Object (`HTTP Status Code`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses\parse` and `Cdd\Responses\emit` |
| **Response Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Stdout formatting (Table format, JSON, YAML, --raw) |
| **Response Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Subcommand exit status short description |
| **Response Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses\parse` and `Cdd\Responses\emit` |
| **Response Object (`headers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses`, `@oas-header`, and `Cdd\Controllers` |
| **Response Object (`content`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses\parse` and `Cdd\Responses\emit` |
| **Response Object (`links`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses\parse` and `Cdd\Responses\emit` |
| **Callback Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Operations` (`@callback`) |
| **Callback Object (`{expression}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Operations` (`@callback`) |
| **Example Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Injected into subcommand --help 'Examples' block |
| **Example Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Example Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Example Object (`dataValue`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Example Object (`serializedValue`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Example Object (`externalValue`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Example Object (`value`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Mocks\parse` and `Cdd\Mocks\emit` |
| **Link Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Link Object (`operationRef`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Link Object (`operationId`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Link Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Link Object (`requestBody`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Link Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Link Object (`server`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components` and `Cdd\Responses` |
| **Header Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Can optionally be printed with -v (verbose) flags |
| **Header Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`required`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`deprecated`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`example`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`examples`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`style`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`explode`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`schema`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Header Object (`content`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Components`, `Cdd\Responses`, and `@oas-header` |
| **Tag Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | High-level CLI command groups (e.g. cli [tag] [operation]) |
| **Tag Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` and `@tag` annotations |
| **Tag Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` |
| **Tag Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` |
| **Tag Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally via `api_metadata.php` |
| **Tag Object (`parent`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Nested subcommand groups |
| **Tag Object (`kind`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Subcommand grouping logic (e.g. nav vs hidden) |
| **Reference Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Resolved & emitted via `Cdd\Openapi\resolve_refs` and `Cdd\Schemas` |
| **Reference Object (`$ref`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Internal resolution to flatten flags/commands |
| **Reference Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved alongside `$ref` in `Cdd\Schemas` and `Cdd\Components` |
| **Reference Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved alongside `$ref` in `Cdd\Schemas` and `Cdd\Components` |
| **Schema Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`discriminator`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@discriminator`) |
| **Schema Object (`xml`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **Schema Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@see`) |
| **Schema Object (`example`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@example`) |
| **Discriminator Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mutually exclusive flag groups based on type |
| **Discriminator Object (`propertyName`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Discriminator Object (`mapping`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Discriminator Object (`defaultMapping`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Fallback CLI flag group when type is omitted |
| **XML Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`nodeType`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`namespace`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`prefix`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`attribute`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`wrapped`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **Security Scheme Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`type`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`in`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`scheme`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`bearerFormat`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`flows`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`openIdConnectUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`oauth2MetadataUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | OAuth2 metadata discovery |
| **Security Scheme Object (`deprecated`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flows Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | CLI token manager / local keychain integration |
| **OAuth Flows Object (`implicit`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flows Object (`password`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flows Object (`clientCredentials`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flows Object (`authorizationCode`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flows Object (`deviceAuthorization`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Support for the Device Authorization grant flow |
| **OAuth Flow Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Flow routing logic for CLI login |
| **OAuth Flow Object (`authorizationUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flow Object (`deviceAuthorizationUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Triggers CLI device auth prompt |
| **OAuth Flow Object (`tokenUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flow Object (`refreshUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **OAuth Flow Object (`scopes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Requirement Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Asserting required auth exists before command execution |
| **Security Requirement Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security`, `Cdd\Operations`, and `Cdd\Client` |
