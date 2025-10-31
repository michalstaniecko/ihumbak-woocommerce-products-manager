# Release Workflow Guide

This repository uses GitHub Actions to automatically create plugin release archives.

## How It Works

The workflow (`.github/workflows/release.yml`) runs automatically when:

1. **Code is pushed to the main branch** - Creates a build artifact
2. **A version tag is pushed** - Creates a GitHub release with the plugin ZIP file

## Creating a Release

To create a new release:

### 1. Update the Version Number

Edit `ihumbak-woocommerce-products-manager.php` and update the version:

```php
* Version: 1.0.1  // Change this to your new version
```

Also update the constant:

```php
define( 'IHUMBAK_WPM_VERSION', '1.0.1' );  // Change this too
```

### 2. Commit Your Changes

```bash
git add ihumbak-woocommerce-products-manager.php
git commit -m "Bump version to 1.0.1"
git push origin main
```

### 3. Create and Push a Tag

```bash
git tag v1.0.1
git push origin v1.0.1
```

### 4. Wait for the Workflow

The GitHub Actions workflow will:
- Create a clean ZIP archive of the plugin
- Create a GitHub release with the archive attached
- The release will be available at: `https://github.com/michalstaniecko/ihumbak-woocommerce-products-manager/releases`

## Download URLs

Each release will have a consistent download URL pattern:

```
https://github.com/michalstaniecko/ihumbak-woocommerce-products-manager/releases/download/v{VERSION}/ihumbak-woocommerce-products-manager.zip
```

For example:
- v1.0.0: `https://github.com/michalstaniecko/ihumbak-woocommerce-products-manager/releases/download/v1.0.0/ihumbak-woocommerce-products-manager.zip`
- v1.0.1: `https://github.com/michalstaniecko/ihumbak-woocommerce-products-manager/releases/download/v1.0.1/ihumbak-woocommerce-products-manager.zip`

## Latest Release

To always get the latest release, users can use:

```
https://github.com/michalstaniecko/ihumbak-woocommerce-products-manager/releases/latest/download/ihumbak-woocommerce-products-manager.zip
```

This URL will automatically redirect to the latest version's ZIP file.

## What's Included in the ZIP

The workflow creates a clean archive that includes:
- All PHP plugin files
- CSS and JavaScript assets
- README.md

The archive excludes development files:
- `.git` and `.github` directories
- `node_modules`
- IDE configuration files (`.vscode`, `.idea`)
- Temporary files (`*.log`, `*.tmp`, `*.bak`)
- Build directories

## Testing Builds Without Creating a Release

When you push to the main branch without a tag, the workflow will:
- Create the ZIP archive
- Upload it as a build artifact (available for 30 days)
- No release will be created

You can download these artifacts from the Actions tab to test them before creating an official release.
