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

## Advanced Mock Capabilities

### Global CORS Middleware
- Permissive CORS headers (`Access-Control-Allow-Origin: *`, standard methods and headers) are enabled by default via `CorsMiddleware`, supporting preflight `OPTIONS` requests (`204 No Content`) and cross-origin requests out of the box.

### Strict Request Validation (`--strict-validation`)
- **Command:** `php src/ServerRunner.php --strict-validation`
- **Behavior:** Enables `ValidationMiddleware` to validate incoming request bodies against the generated OpenAPI/MCP schema constraints, returning detailed `400 Bad Request` errors (e.g., `\"Field 'email' must match format 'email'\"`, `\"Array 'items' exceeds maximum length\"`).

### Hybrid Authentication (`--enforce-auth`)
- **Command:** `php src/ServerRunner.php --enforce-auth`
- **Mock Mode:** When running with `--ephemeral` or without `DATABASE_URL`, validates against deterministic mock credentials (e.g., `Bearer mock-token-123`, `mock-api-key-123`) and returns `401 Unauthorized` or `403 Forbidden` when credentials or scopes are missing/invalid.
- **Production Mode:** When connected to `DATABASE_URL` without `--ephemeral`, integrates directly with Concrete DAOs to verify sessions/tokens against persisted database records.

### Integrated Identity Provider (`--start-auth-server`)
- **Command:** `php src/ServerRunner.php --start-auth-server`
- **Endpoints:** Mounts `POST /auth/register`, `POST /auth/login`, `POST /auth/refresh`, and `POST /auth/logout` backed by the shared domain DAO architecture, with native SDK support via `\$client->auth->login(\$username, \$password)`.

### Administrative Webhook & Callback Trigger API
- **Endpoint:** `POST /_mock/trigger-webhook/{webhook_name}`
- **Behavior:** Accepts a JSON payload containing `target_url` and `payload`, and uses `WebhookDispatcher` to dispatch outgoing webhook or callback HTTP requests to the registered receiver URL with the `X-Webhook-Event` header.

## Code Generation Lifecycle & Synchronization

This project was generated using `cdd-php`. To maintain contract harmony:
- Use `cdd-php from_openapi to_server` to re-scaffold the server from an updated OpenAPI spec.
- Use `cdd-php to_openapi` to derive the OpenAPI spec directly from any custom modifications made to the DAOs or route handlers.
- Use `cdd-php sync --truth class` (or other truth targets) to bidirectionally align classes, specifications, and DAOs, preventing contract drift.

";
}
