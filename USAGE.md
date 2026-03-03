# Usage

The `cdd-php` compiler provides a versatile CLI that can be used directly or automated via `make`.

## Getting Started

Check version:

```bash
php bin/cdd-php --version
```

Get help for all commands:

```bash
php bin/cdd-php --help
```

## Basic Workflows

### 1. Generating SDK Client

Use `from_openapi to_sdk` to scaffold a client from a spec:

```bash
php bin/cdd-php from_openapi to_sdk -i openapi.json -o src/Client
```

### 2. Generating Server Routes

```bash
php bin/cdd-php from_openapi to_server -i openapi.json -o src/Server
```

### 3. Generating CLI Client

This will create an offline, fully-typed CLI client:

```bash
php bin/cdd-php from_openapi to_sdk_cli -i openapi.json -o src/Cli
```

### 4. Extracting OpenAPI from PHP

Point it to your source code and it will statically analyze it to output OpenAPI:

```bash
php bin/cdd-php to_openapi -f src/routes.php -o spec.json
```

### 5. Serving JSON RPC

```bash
php bin/cdd-php serve_json_rpc --port 8082 --listen 0.0.0.0
```

### 6. Environment Variables

All CLI arguments can be passed via environment variables by prefixing them with `CDD_`. For example, `CDD_INPUT_DIR=./specs` maps to `--input-dir ./specs`.
