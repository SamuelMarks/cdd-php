<?php

declare(strict_types=1);

namespace Cdd\Readme;

function emit(string $appName): string
{
    $out = "# {$appName} Server\n\nThis is an auto-generated OpenAPI/MCP Server leveraging Data Access Objects (DAOs) and a multi-tiered connection architecture.\n\n## Server Modes\n\nThe server entrypoint (`php src/ServerRunner.php`) supports the following orthogonal execution modes:\n\n### 1. Stub Mode\n- **Command:** `php src/ServerRunner.php` (with NO `DATABASE_URL` configured)\n- **Behavior:** The server routes requests to `StubDao` implementations. All database operations will safely throw a `NotImplementedError` or return empty payloads.\n\n### 2. Production Mode\n- **Command:** `DATABASE_URL=postgres://user:pass@host/db php src/ServerRunner.php`\n- **Behavior:** The server connects to the actual database specified in the URL and utilizes the `ConcreteDao` objects for persistent ORM operations.\n\n### 3. Sandbox Mode\n- **Command:** `php src/ServerRunner.php --ephemeral`\n- **Behavior:** Bypasses `DATABASE_URL` entirely. Injects an isolated, in-memory SQLite database (`:memory:`) and automatically executes schema migrations. Perfect for isolated, throwaway integration tests.\n\n### 4. Full Mock Mode\n- **Command:** `php src/ServerRunner.php --ephemeral --seed`\n- **Behavior:** Boots an in-memory database and populates it with a rich, topologically sorted graph of mock data using Faker, strictly adhering to foreign key relations. Ideal for UI development and E2E client testing without manual data entry.\n\n## Code Generation Lifecycle & Synchronization\n\nThis project was generated using `cdd-php`. To maintain contract harmony:\n- Use `cdd-php from_openapi to_server` to re-scaffold the server from an updated OpenAPI spec.\n- Use `cdd-php to_openapi` to derive the OpenAPI spec directly from any custom modifications made to the DAOs or route handlers.\n- Use `cdd-php sync --truth class` (or other truth targets) to bidirectionally align classes, specifications, and DAOs, preventing contract drift.\n\n";
    return $out;
}
