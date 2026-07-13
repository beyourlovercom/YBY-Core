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
