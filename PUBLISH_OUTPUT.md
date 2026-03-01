# Publishing Generated APIs (Clients/SDKs)

When `cdd-php` generates an SDK, you can publish it just like any regular PHP package to Packagist, or keep it as a private repository.

1. **Tag a Release:** Ensure all generated changes are committed to the generated repository and tag a version.
   `git tag -a v0.0.1 -m "SDK v0.0.1"`
2. **Push Tags:** 
   `git push origin --tags`
3. **Packagist:** Log into [Packagist.org](https://packagist.org/), submit your generated GitHub repository URL.

## Automated Updates via GitHub Actions

To keep the client library automatically up-to-date with your API server's OpenAPI spec, you can create a cron job in GitHub Actions that periodically pulls the spec, regenerates the code, and commits/publishes the changes.

Example `.github/workflows/sync-sdk.yml`:

```yaml
name: Sync SDK
on:
  schedule:
    - cron: '0 0 * * *' # Daily at midnight
  workflow_dispatch:

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Fetch Latest Spec
        run: curl -sL "https://api.yourdomain.com/openapi.json" -o openapi.json
      - name: Generate SDK
        run: |
          wget https://github.com/offscale/cdd-php/releases/download/v0.0.1/cdd-php.phar
          php cdd-php.phar from_openapi to_sdk -i openapi.json -o .
      - name: Commit and Push Changes
        run: |
          git config --global user.name "GitHub Action"
          git config --global user.email "action@github.com"
          git add .
          git diff --quiet && git diff --staged --quiet || (git commit -m "Auto-update SDK to match spec" && git push)
```