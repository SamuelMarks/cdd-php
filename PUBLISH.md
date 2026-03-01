# Publishing `cdd-php`

This guide explains how to release the `cdd-php` compiler library to the general public.

## 1. Publishing to Packagist

Packagist is the primary repository for PHP packages. 

1. Create a `composer.json` at the root of your project if it doesn't already exist.
2. Commit and push your changes to GitHub.
3. Sign in to [Packagist.org](https://packagist.org/) using your GitHub account.
4. Click **Submit** and provide your repository URL.
5. Create a new GitHub Release (e.g., `v1.0.0`). Packagist automatically detects the tag and publishes the package versions.

## 2. Publishing Documentation

### Local Static Folder
You can build the docs locally using:
```bash
make build_docs
```
This generates a static documentation folder at `docs/`. You can serve it locally using PHP's built-in server:
```bash
php -S localhost:8000 -t docs
```

### Remote Hosting (GitHub Pages)
The simplest way to host the `docs/` folder is via GitHub pages.
1. Create a `.github/workflows/pages.yml`.
2. Configure it to deploy the `docs/` output to the `gh-pages` branch.
3. Your docs will be live at `https://<user>.github.io/<repo>`.
