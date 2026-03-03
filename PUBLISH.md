# Publishing `cdd-php`

## Composer Package (`Packagist`)

The `cdd-php` compiler tool is a standard PHP library managed via Composer.
To publish a new release:

1. Update the `version` field in `composer.json` (currently 0.0.1).
2. Commit the changes.
3. Push to `main` branch.
4. Tag a new release (e.g. `v0.0.1`) on GitHub.

Packagist is integrated with GitHub, so creating a GitHub Release or pushing a tag will automatically sync it to [Packagist.org](https://packagist.org) allowing users to install it via:

```bash
composer require offscale/cdd-php
```

## Publishing API Docs

To generate the documentation site API configuration:

1. Generate the JSON documentation format using the CLI tool:
   ```bash
   make build_docs
   ```
2. The `docs.json` artifact will be placed in `docs/` or the configured location.
3. This artifact is intended to be parsed and rendered by a static site generator or uploaded to a shared `cdd-*` static assets server.
