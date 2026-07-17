# Packaging

## Packaging source

Build packages from Git-tracked plugin runtime source only.

Do not package from:

- historical repository copies
- extracted ZIP folders
- WordPress uploads
- temporary export directories

## Required ZIP root

The ZIP must extract to:

`yby-core/`

## ZIP internal path separator validation

All release ZIP packages must use Unix-style path separators.

Required:

- `/`

Forbidden:

- `\`

Forbidden example:

- `yby-core\yby-core.php`

Required example:

- `yby-core/yby-core.php`

## ZIP validation command

Before declaring a package release-ready, validated, or safe to upload, run:

```bash
unzip -l yby-core-vX.X.X.zip
```

Expected output example:

```text
yby-core/yby-core.php
yby-core/inc/
yby-core/public/
```

Invalid output:

```text
yby-core\yby-core.php
```

The package must pass:

- ZIP root directory validation
- Main plugin file validation
- Internal path separator validation

## Required runtime scope

Include only the governed runtime payload:

- `yby-core.php`
- `inc/`
- `admin/`
- `public/`
- `assets/`
- `modules/`
- `templates/`
- `languages/`
- `README.md`
- `CHANGELOG.md`
- `readme.txt`

## Exclusions

Do not include:

- `.git/`
- `.github/`
- `docs/`
- `releases/`
- temporary files
- local environment files
- unrelated bootstrap material

## Manifest requirement

Each package must ship with a manifest containing:

- repository remote
- source branch
- source commit hash
- build date
- plugin version
- release stage
- file count
- ZIP SHA256
- PHP lint status
- `git diff --check` status

## Release checklist

- [ ] ZIP internal paths use `/` separators only
