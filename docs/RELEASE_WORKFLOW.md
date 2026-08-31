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
-> Tagged Build Artifact
-> Explicit Owner GitHub Release Authorization
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

Stable release package name:

- `andy-core-vX.X.X.zip`

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
unzip -l andy-core-vX.X.X.zip
```

The package must contain:

- `yby-core/yby-core.php`

## SHA256 standard

Every release package must generate:

- `SHA256.txt`

Command:

```bash
sha256sum andy-core-vX.X.X.zip > SHA256.txt
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

## Tag and GitHub Release authorization gates

Creating or pushing `vX.X.X` is an explicit Owner authorization for the tag only. The tag workflow validates PHP, derives the version from the tag, requires `scripts/build-vX.X.X-release.sh`, invokes it with `BUILD_CONTEXT=FINAL_RELEASE` and `RELEASE_OUTPUT_DIR=releases/vX.X.X`, validates the resulting package, and uploads `releases/vX.X.X` as an Actions artifact.

The tag workflow must not create or update a GitHub Release. Publishing a GitHub Release is a separate explicit Owner authorization gate after the tagged build artifact and release validation have passed. Only then may the package, checksum, and release documentation be attached to the GitHub Release.

## GitHub Release standard

Each GitHub Release must include:

- ZIP package
- `SHA256.txt`
- Release notes
- Upgrade guide
- Rollback guide

## GitHub Actions automation

Create `.github/workflows/release.yml` with a `push` trigger for tags matching `v*`.

The tag-build workflow should:

- Check out the repository
- Set up PHP
- Run PHP syntax validation
- Require and invoke `scripts/build-vX.X.X-release.sh` with final-release context
- Build `releases/vX.X.X/andy-core-vX.X.X.zip`
- Validate the ZIP root, exclusions, and version metadata
- Upload `releases/vX.X.X` only as an Actions artifact
- Never call `softprops/action-gh-release` or publish/update a GitHub Release

## Current stable release

- Stable version: `YBY Core v1.5.2`
- Stable package baseline: `andy-core-v1.5.2.zip`
