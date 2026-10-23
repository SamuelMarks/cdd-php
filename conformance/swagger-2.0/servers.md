# Swagger 2.0 Conformance Table: Servers (ORM + REST API + Mocks + Tests)

This table tracks the completeness of language integration with Swagger 2.0 for server-side generation and extraction.

### Legend & Tracking Guide
*   **To**: Language -> OpenAPI (Generating the Swagger document from code/types/decorators)
*   **From**: OpenAPI -> Language (Generating server stubs/types/ORM models from the Swagger document)
*   **Presence `[To, From]`**: The object is successfully parsed, validated, utilized, or generated.
*   **Absence `[To, From]`**: The object is currently unsupported, dropped, or falls back to generic/`any` types.
*   **Skipped `[To, From]`**: Intentionally ignored because it is irrelevant or unsupported by the architecture.
*   **Checkboxes**: Mark `[x]` as conformance is achieved.

| Swagger 2.0 Object / Feature | Presence `[To, From]` | Absence `[To, From]` | Skipped `[To, From]` | Notes / Implementation Strategy |
| :--- | :---: | :---: | :---: | :--- |
| **Swagger Object (Root)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Root Swagger 2.0 specification parsed & emitted via `Cdd\Openapi\parse` and `Cdd\Openapi\emit` (`target_version: 2.0`) |
| **Swagger Object (`swagger`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded from `"2.0"` via `upgrade_swagger2_to_openapi3` and downgraded via `downgradeOpenApi3ToSwagger2` |
| **Swagger Object (`info`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved across `Cdd\Openapi\swagger2` and `Cdd\Info` |
| **Swagger Object (`host`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `servers[0].url` host & port in `Cdd\Openapi\swagger2` |
| **Swagger Object (`basePath`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `servers[0].url` path in `Cdd\Openapi\swagger2` |
| **Swagger Object (`schemes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `servers[*].url` schemes in `Cdd\Openapi\swagger2` |
| **Swagger Object (`consumes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from operation request body media types in `Cdd\Openapi\swagger2` |
| **Swagger Object (`produces`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from operation response media types in `Cdd\Openapi\swagger2` |
| **Swagger Object (`paths`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded & downgraded via `Cdd\Openapi\swagger2` and `Cdd\Paths` |
| **Swagger Object (`definitions`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.schemas` (`#/definitions/` <-> `#/components/schemas/`) |
| **Swagger Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.parameters` (`#/parameters/` <-> `#/components/parameters/`) |
| **Swagger Object (`responses`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.responses` (`#/responses/` <-> `#/components/responses/`) |
| **Swagger Object (`securityDefinitions`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.securitySchemes` (`#/securityDefinitions/` <-> `#/components/securitySchemes/`) |
| **Swagger Object (`security`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `Cdd\Security` |
| **Swagger Object (`tags`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Swagger Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Swagger Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Root `x-*` specification extensions preserved in `upgrade_swagger2_to_openapi3` and `downgradeOpenApi3ToSwagger2` |
| **Info Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **Info Object (`title`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@title`) |
| **Info Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **Info Object (`termsOfService`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@termsOfService`) |
| **Info Object (`contact`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@contact`) |
| **Info Object (`license`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@license`) |
| **Info Object (`version`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` (`@version`) |
| **Info Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `info` via `Cdd\Info` and `api_metadata.php` |
| **Contact Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **Contact Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@contact` PHPDoc and `api_metadata.php` |
| **Contact Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@contact` PHPDoc and `api_metadata.php` |
| **Contact Object (`email`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@contact` PHPDoc and `api_metadata.php` |
| **Contact Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `info.contact` via `api_metadata.php` |
| **License Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Info\parse` and `Cdd\Info\emit` |
| **License Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@license` PHPDoc and `api_metadata.php` |
| **License Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Extracted & emitted in `@license` PHPDoc and `api_metadata.php` |
| **License Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `info.license` via `api_metadata.php` |
| **Paths Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Paths Object (`/{path}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Paths Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on path items and paths via `Cdd\Paths` and `Cdd\Openapi\swagger2` |
| **Path Item Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Paths\parse` and `Cdd\Paths\emit` |
| **Path Item Object (`$ref`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Rewritten & resolved via `Cdd\Openapi\swagger2` and `Cdd\Openapi\resolve_refs` |
| **Path Item Object (`get`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`put`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`post`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`delete`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`options`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`head`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`patch`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Routes`, `Cdd\Client`, and `Cdd\Cli` |
| **Path Item Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded & downgraded via `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Path Item Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on Path Item Object across `Cdd\Openapi\swagger2` and `Cdd\Paths` |
| **Operation Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations`, `Cdd\Controllers`, `Cdd\Client`, and `Cdd\Cli` |
| **Operation Object (`tags`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@tag`) |
| **Operation Object (`summary`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` |
| **Operation Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` |
| **Operation Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@see`) |
| **Operation Object (`operationId`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to PHP method/command names in `Cdd\Operations`, `Cdd\Client`, and `Cdd\Cli` |
| **Operation Object (`consumes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `requestBody.content` media types in `Cdd\Openapi\swagger2` |
| **Operation Object (`produces`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `responses[*].content` media types in `Cdd\Openapi\swagger2` |
| **Operation Object (`parameters`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped between Swagger 2.0 `parameters` (including `body`/`formData`) and OAS 3 `parameters`/`requestBody` |
| **Operation Object (`responses`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped between Swagger 2.0 `responses[*].schema` and OAS 3 `responses[*].content` |
| **Operation Object (`schemes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from operation-level `servers` in `Cdd\Openapi\swagger2` |
| **Operation Object (`deprecated`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@deprecated`) |
| **Operation Object (`security`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Operations\parse` and `Cdd\Operations\emit` (`@security`) |
| **Operation Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on Operation Object across `Cdd\Openapi\swagger2` |
| **External Documentation Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `@see` PHPDoc annotations in `Cdd\Docstrings` |
| **External Documentation Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `@see` PHPDoc annotations in `Cdd\Docstrings` |
| **External Documentation Object (`url`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `@see` PHPDoc annotations in `Cdd\Docstrings` |
| **External Documentation Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `externalDocs` via `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Parameter Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded & downgraded via `Cdd\Openapi\swagger2` and `Cdd\Parameters` |
| **Parameter Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to PHP parameter/flag name in `Cdd\Parameters`, `Cdd\Client`, and `Cdd\Cli` |
| **Parameter Object (`in`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Supports `query`, `header`, `path`, `formData`, and `body` in `Cdd\Openapi\swagger2` |
| **Parameter Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved across `body`, `formData`, and inline parameters in `Cdd\Openapi\swagger2` |
| **Parameter Object (`required`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved across `body`, `formData` (`schema.required`), and inline parameters |
| **Parameter Object (`schema`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `requestBody.content[mime].schema` in `Cdd\Openapi\swagger2` |
| **Parameter Object (`type`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.type` and `formData` property schema in `Cdd\Openapi\swagger2` |
| **Parameter Object (`format`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.format` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`allowEmptyValue`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on query and `formData` parameters in `Cdd\Openapi\swagger2` and `Cdd\Parameters` |
| **Parameter Object (`items`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.items` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`collectionFormat`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Supported via `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` and controller `@param` metadata |
| **Parameter Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.default` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`maximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.maximum` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`exclusiveMaximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.exclusiveMaximum` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`minimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.minimum` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`exclusiveMinimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.exclusiveMinimum` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`maxLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.maxLength` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`minLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.minLength` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`pattern`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.pattern` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`maxItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.maxItems` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`minItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.minItems` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`uniqueItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.uniqueItems` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`enum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.enum` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`multipleOf`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.multipleOf` in `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Parameter Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on parameters and `@oas-param` annotations |
| **Items Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `schema.items` across parameters and headers in `Cdd\Openapi\swagger2` |
| **Items Object (`type`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.type` across `Cdd\Openapi\swagger2` and `Cdd\Parameters` |
| **Items Object (`format`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.format` across `Cdd\Openapi\swagger2` and `Cdd\Parameters` |
| **Items Object (`items`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved recursively in nested `items.items` across `Cdd\Openapi\swagger2` |
| **Items Object (`collectionFormat`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Supported via nested `items.collectionFormat` and controller `@param [itemsCollectionFormat=...]` metadata |
| **Items Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.default` across `Cdd\Openapi\swagger2` |
| **Items Object (`maximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.maximum` across `Cdd\Openapi\swagger2` |
| **Items Object (`exclusiveMaximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.exclusiveMaximum` across `Cdd\Openapi\swagger2` |
| **Items Object (`minimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.minimum` across `Cdd\Openapi\swagger2` |
| **Items Object (`exclusiveMinimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.exclusiveMinimum` across `Cdd\Openapi\swagger2` |
| **Items Object (`maxLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.maxLength` across `Cdd\Openapi\swagger2` |
| **Items Object (`minLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.minLength` across `Cdd\Openapi\swagger2` |
| **Items Object (`pattern`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.pattern` across `Cdd\Openapi\swagger2` |
| **Items Object (`maxItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.maxItems` across `Cdd\Openapi\swagger2` |
| **Items Object (`minItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.minItems` across `Cdd\Openapi\swagger2` |
| **Items Object (`uniqueItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.uniqueItems` across `Cdd\Openapi\swagger2` |
| **Items Object (`enum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.enum` across `Cdd\Openapi\swagger2` |
| **Items Object (`multipleOf`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items.multipleOf` across `Cdd\Openapi\swagger2` |
| **Items Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `items` across `Cdd\Openapi\swagger2` |
| **Responses Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Openapi\swagger2` and `Cdd\Responses` |
| **Responses Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Openapi\swagger2` and `Cdd\Responses` |
| **Responses Object (`{HTTP Status Code}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Openapi\swagger2` and `Cdd\Responses` |
| **Responses Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `responses` across `Cdd\Openapi\swagger2` |
| **Response Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped between Swagger 2.0 `schema`/`examples`/`headers` and OAS 3 `content`/`headers` |
| **Response Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses\parse` and `Cdd\Responses\emit` |
| **Response Object (`schema`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `content[mime].schema` in `Cdd\Openapi\swagger2` |
| **Response Object (`headers`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded & downgraded via `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Response Object (`examples`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `content[mime].example` in `Cdd\Openapi\swagger2` |
| **Response Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on Response Object across `Cdd\Openapi\swagger2` |
| **Headers Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Responses`, `@oas-header`, and `Cdd\Openapi\swagger2` |
| **Headers Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped by header name in `responses[*].headers` and `@oas-header` |
| **Example Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped between Swagger 2.0 `examples[mime]` and OAS 3 `content[mime].example` |
| **Example Object (`{mime type}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped per MIME type in `upgrade_swagger2_to_openapi3` and `downgradeOpenApi3ToSwagger2` |
| **Header Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded & downgraded via `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` |
| **Header Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in header definition and `@oas-header` PHPDoc annotations |
| **Header Object (`type`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.type` in `Cdd\Openapi\swagger2` |
| **Header Object (`format`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.format` in `Cdd\Openapi\swagger2` |
| **Header Object (`items`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.items` in `Cdd\Openapi\swagger2` |
| **Header Object (`collectionFormat`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Supported via `upgrade_swagger2_inline_schema` / `downgrade_openapi3_inline_schema` and `@oas-header` annotations |
| **Header Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.default` in `Cdd\Openapi\swagger2` |
| **Header Object (`maximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.maximum` in `Cdd\Openapi\swagger2` |
| **Header Object (`exclusiveMaximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.exclusiveMaximum` in `Cdd\Openapi\swagger2` |
| **Header Object (`minimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.minimum` in `Cdd\Openapi\swagger2` |
| **Header Object (`exclusiveMinimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.exclusiveMinimum` in `Cdd\Openapi\swagger2` |
| **Header Object (`maxLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.maxLength` in `Cdd\Openapi\swagger2` |
| **Header Object (`minLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.minLength` in `Cdd\Openapi\swagger2` |
| **Header Object (`pattern`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.pattern` in `Cdd\Openapi\swagger2` |
| **Header Object (`maxItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.maxItems` in `Cdd\Openapi\swagger2` |
| **Header Object (`minItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.minItems` in `Cdd\Openapi\swagger2` |
| **Header Object (`uniqueItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.uniqueItems` in `Cdd\Openapi\swagger2` |
| **Header Object (`enum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.enum` in `Cdd\Openapi\swagger2` |
| **Header Object (`multipleOf`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `header.schema.multipleOf` in `Cdd\Openapi\swagger2` |
| **Header Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved on Header Object and `@oas-header` annotations |
| **Tag Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Tag Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `@tag` annotations |
| **Tag Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Tag Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved bidirectionally in `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Tag Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `tags` via `Cdd\Openapi\swagger2` and `api_metadata.php` |
| **Reference Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Rewritten bidirectionally via `rewrite_ref_prefixes` in `Cdd\Openapi\swagger2` |
| **Reference Object (`$ref`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Rewritten between `#/definitions|parameters|responses|securityDefinitions/` and `#/components/...` |
| **Schema Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`$ref`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Rewritten between `#/definitions/` and `#/components/schemas/` in `Cdd\Openapi\swagger2` |
| **Schema Object (`format`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`title`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`default`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`multipleOf`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`maximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`exclusiveMaximum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`minimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`exclusiveMinimum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`maxLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`minLength`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`pattern`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`maxItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`minItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`uniqueItems`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`maxProperties`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`minProperties`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`required`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`enum`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`type`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`items`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`allOf`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`properties`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`additionalProperties`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` |
| **Schema Object (`discriminator`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@discriminator`) |
| **Schema Object (`readOnly`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`readonly`) |
| **Schema Object (`xml`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **Schema Object (`externalDocs`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@see`) |
| **Schema Object (`example`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@example`) |
| **Schema Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved via `@oas-schema-ext` PHPDoc annotations in `Cdd\Schemas` |
| **XML Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`namespace`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`prefix`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`attribute`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`wrapped`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **XML Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Schemas\parse` and `Cdd\Schemas\emit` (`@xml`) |
| **Definitions Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.schemas` in `Cdd\Openapi\swagger2` |
| **Definitions Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from PHP class names in `Cdd\Schemas` and `Models.php` |
| **Parameters Definitions Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.parameters` in `Cdd\Openapi\swagger2` |
| **Parameters Definitions Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped by parameter definition key in `Cdd\Openapi\swagger2` and `Cdd\Components` |
| **Responses Definitions Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.responses` in `Cdd\Openapi\swagger2` |
| **Responses Definitions Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped by response definition key in `Cdd\Openapi\swagger2` and `Cdd\Components` |
| **Security Definitions Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `components.securitySchemes` in `Cdd\Openapi\swagger2` |
| **Security Definitions Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped by security scheme key in `Cdd\Openapi\swagger2` and `Cdd\Security` |
| **Security Scheme Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Upgraded & downgraded between Swagger 2.0 `securityDefinitions` and OAS 3 `securitySchemes` |
| **Security Scheme Object (`type`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Maps `basic` <-> `http` (`scheme: basic`), `apiKey`, and `oauth2` in `Cdd\Openapi\swagger2` |
| **Security Scheme Object (`description`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`name`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`in`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security\parse` and `Cdd\Security\emit` |
| **Security Scheme Object (`flow`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Maps `implicit`, `password`, `application` <-> `clientCredentials`, and `accessCode` <-> `authorizationCode` |
| **Security Scheme Object (`authorizationUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `flows[*].authorizationUrl` in `Cdd\Openapi\swagger2` |
| **Security Scheme Object (`tokenUrl`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `flows[*].tokenUrl` in `Cdd\Openapi\swagger2` |
| **Security Scheme Object (`scopes`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `flows[*].scopes` in `Cdd\Openapi\swagger2` |
| **Security Scheme Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in security scheme definitions via `Cdd\Openapi\swagger2` and `Cdd\Security` |
| **Scopes Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped to/from `flows[*].scopes` in `Cdd\Openapi\swagger2` and `Cdd\Security` |
| **Scopes Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped by scope name -> description in `flows[*].scopes` |
| **Scopes Object (`^x-`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Preserved in `scopes` map across `Cdd\Openapi\swagger2` |
| **Security Requirement Object** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Parsed & emitted via `Cdd\Security`, `Cdd\Operations`, and `Cdd\Client` |
| **Security Requirement Object (`{name}`)** | `[x]` , `[x]` | `[x]` , `[x]` | `[x]` , `[x]` | Mapped by security scheme name -> required scopes array |
