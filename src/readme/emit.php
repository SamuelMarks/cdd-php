<?php

/**
 * Emits README documentation for generated OpenAPI/MCP servers.
 *
 * @package Cdd\Readme
 */

declare(strict_types=1);

namespace Cdd\Readme;

/**
 * Emits a Markdown README for a generated server project.
 *
 * @param string $appName The name of the generated application.
 * @return string The generated Markdown documentation.
 */
function emit(string $appName): string
{
    return "# {$appName} Server

This is an auto-generated OpenAPI/MCP Server leveraging Data Access Objects (DAOs) and a multi-tiered connection architecture.

## Server Modes

The server entrypoint (`php src/ServerRunner.php`) supports the following orthogonal execution modes:

### 1. Stub Mode
- **Command:** `php src/ServerRunner.php` (with NO `DATABASE_URL` configured)
- **Behavior:** The server routes requests to `StubDao` implementations. All database operations will safely throw a `NotImplementedError` or return empty payloads.

### 2. Production Mode
- **Command:** `DATABASE_URL=postgres://user:pass@host/db php src/ServerRunner.php`
- **Behavior:** The server connects to the actual database specified in the URL and utilizes the `ConcreteDao` objects for persistent ORM operations.

### 3. Sandbox Mode
- **Command:** `php src/ServerRunner.php --ephemeral`
- **Behavior:** Bypasses `DATABASE_URL` entirely. Injects an isolated, in-memory SQLite database (`:memory:`) and automatically executes schema migrations. Perfect for isolated, throwaway integration tests.

### 4. Full Mock Mode
- **Command:** `php src/ServerRunner.php --ephemeral --seed`
- **Behavior:** Boots an in-memory database and populates it with a rich, topologically sorted graph of mock data using Faker, strictly adhering to foreign key relations. Ideal for UI development and E2E client testing without manual data entry.

## Code Generation Lifecycle & Synchronization

This project was generated using `cdd-php`. To maintain contract harmony:
- Use `cdd-php from_openapi to_server` to re-scaffold the server from an updated OpenAPI spec.
- Use `cdd-php to_openapi` to derive the OpenAPI spec directly from any custom modifications made to the DAOs or route handlers.
- Use `cdd-php sync --truth class` (or other truth targets) to bidirectionally align classes, specifications, and DAOs, preventing contract drift.

";
}
