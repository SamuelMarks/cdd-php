# Usage

The `cdd-php` compiler provides a standalone CLI, a programmatic PHP SDK, an HTTP JSON-RPC 2.0 server, and a Model Context Protocol (MCP) server over stdio.

## Getting Started

Check the installed version:

```bash
php bin/cdd-php --version
```

Display top-level help:

```bash
php bin/cdd-php --help
```

## CLI Workflows

### 1. Generating a Client SDK (`from_openapi to_sdk`)

```bash
php bin/cdd-php from_openapi to_sdk -i openapi.json -o ./sdk --tests --mcp
```

### 2. Generating a Client SDK + CLI (`from_openapi to_sdk_cli`)

```bash
php bin/cdd-php from_openapi to_sdk_cli -i openapi.json -o ./cli --tests --mcp
```

### 3. Generating a Server (`from_openapi to_server`)

```bash
php bin/cdd-php from_openapi to_server -i openapi.json -o ./server --tests --mcp
```

Generated server projects include a multi-tiered DAO layer (`StubDao`, `ConcreteDao`, `DaoFactory`), Eloquent models (`src/Models.php`), Laravel-compatible routes (`src/routes.php`), a standalone PHP built-in web server entrypoint (`php -S 127.0.0.1:8080 server.php`), and `src/ServerRunner.php` supporting four execution modes:

- **Stub Mode**: `php src/ServerRunner.php` (when `DATABASE_URL` is unset)
- **Production Mode**: `DATABASE_URL=postgres://user:pass@host:5432/db php src/ServerRunner.php`
- **Sandbox Mode**: `php src/ServerRunner.php --ephemeral` (in-memory `:memory:` SQLite with auto-migrations)
- **Full Mock Mode**: `php src/ServerRunner.php --ephemeral --seed` (in-memory SQLite seeded via `FakerPHP/Faker`)

### 4. Extracting an OpenAPI 3.2.0 Spec from PHP (`to_openapi`)

```bash
php bin/cdd-php to_openapi -i ./server/src/routes.php -o openapi.json
```

### 5. Compiling and Extracting Arazzo 1.1.0 Workflows (`from_arazzo` / `to_arazzo`)

```bash
# Compile Arazzo workflows into PHP SDK, CLI, or Server/E2E workflow tests
php bin/cdd-php from_arazzo to_sdk -i workflow.arazzo.json -o ./workflows
php bin/cdd-php from_arazzo to_sdk_cli -i workflow.arazzo.json -o ./workflow-cli
php bin/cdd-php from_arazzo to_server -i workflow.arazzo.json -o ./workflow-server

# Reverse-parse PHP workflow code back into an Arazzo 1.1.0 specification
php bin/cdd-php to_arazzo -i ./workflows -o extracted.arazzo.json
```

### 6. Bidirectional Synchronization (`sync`)

Synchronize `openapi.json`, `arazzo.json`, models, routes, controllers, SDK clients, CLI commands, servers, webhooks, mocks, and composable tests in-place:

```bash
php bin/cdd-php sync -i ./project -t class
# or designate the specification as the source of truth:
php bin/cdd-php sync -i ./project -t spec
```

### 7. Generating Documentation Code Snippets (`to_docs_json`)

```bash
php bin/cdd-php to_docs_json --no-imports --no-wrapping -i openapi.json -o docs.json
```

### 8. Serving JSON-RPC 2.0 and MCP (`serve_json_rpc` / `mcp`)

```bash
# Expose the compiler over TCP HTTP JSON-RPC 2.0
php bin/cdd-php serve_json_rpc --port 8080 --listen 127.0.0.1 --timeout 60

# Run as a Model Context Protocol (MCP) server over stdio
php bin/cdd-php mcp
```

### 9. Environment Variables

All CLI options can be passed via environment variables prefixed with `CDD_`. Every `CDD_<KEY>=<VALUE>` variable is automatically mapped to `--<key> <value>` (for example, `CDD_INPUT_DIR=./specs` maps to `--input-dir ./specs`, and `CDD_PORT=8082` maps to `--port 8082`).

## Programmatic SDK Usage

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Cdd\Cli\CddCli;
use Cdd\Cli\CddConfig;

$config = new CddConfig();
$config->target = 'to_sdk';
$config->input = 'openapi.json';
$config->output = './generated_sdk';
$config->tests = true;

CddCli::generate_from_openapi($config);
CddCli::generate_to_openapi(['-i', './generated_sdk/src/ApiClient.php', '-o', './roundtrip.openapi.json']);
```
