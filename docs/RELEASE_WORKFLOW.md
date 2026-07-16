# YBY Core Release Workflow

This document defines the mandatory release lifecycle for all future YBY Core releases.

## Official workflow

Development
-> Code Review
-> Testing
-> PHP Syntax Validation
-> Package Build
-> ZIP Validation
-> SHA256 Generation
-> Git Commit
-> Git Tag
-> GitHub Release
-> Production Upload

## Required release documentation

Every release must include:

- `VERSION.md`
- `CHANGELOG.md`
- `RELEASE_NOTES_vX.X.X.md`
- `UPGRADE_GUIDE_vX.X.X.md`
- `ROLLBACK_GUIDE_vX.X.X.md`

## Mandatory PHP validation

Before every release, execute:

```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

Required result:

- Every PHP file must output `No syntax errors detected`

Hard rule:

- If PHP lint is not successfully executed, do not report `release-ready`, `validated`, or `safe to upload`
- Required status: `Prepared but not release-ready because PHP CLI lint was not executed.`

## ZIP packaging standard

Release package name:

- `yby-core-vX.X.X.zip`

ZIP root structure:

```text
yby-core/
    yby-core.php
    inc/
    admin/
    public/
    assets/
    modules/
```

Forbidden:

- `yby-core/yby-core/yby-core.php`

Validation:

```bash
unzip -l yby-core-vX.X.X.zip
```

The package must contain:

- `yby-core/yby-core.php`

## SHA256 standard

Every release package must generate:

- `SHA256.txt`

Command:

```bash
sha256sum yby-core-vX.X.X.zip > SHA256.txt
```

## Version management

Every release must verify:

- Plugin header version: `Version: X.X.X`
- Runtime constant: `define( 'YBY_CORE_VERSION', 'X.X.X' );`
- WordPress readme stable tag: `Stable tag: X.X.X`

All versions must match.

## Git release standard

Commit message:

- Feature release: `release: publish YBY Core vX.X.X`
- Bug fix release: `fix: publish YBY Core vX.X.X`

## Git tag standard

Create tag:

- `vX.X.X`

Tag message:

- `YBY Core vX.X.X Stable Release`

## GitHub Release standard

Each GitHub Release must include:

- ZIP package
- `SHA256.txt`
- Release notes
- Upgrade guide
- Rollback guide

## GitHub Actions automation

Create `.github/workflows/release.yml` with a `push` trigger for tags matching `v*`.

The workflow should:

- Check out the repository
- Set up PHP
- Run PHP syntax validation
- Build the ZIP package
- Generate the SHA256 checksum
- Upload release artifacts

## Current stable release

- Stable version: `YBY Core v1.1.1`
- Brand Settings: included and verified
