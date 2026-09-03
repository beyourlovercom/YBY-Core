# YBY Core Development Environment

## Purpose

Define local environment configuration rules for YBY Core development.

## Mandatory Local-First Flow

The mandatory development and delivery flow is:

1. GitHub/GPT analysis and task definition.
2. Windows Local Codex implementation.
3. Local test/UAT.
4. Dev environment-specific UAT, only after Local test/UAT passes.
5. WWW production deployment, only after Dev UAT passes and with separate authorization.

Dev and WWW must not be used as the default development environment.

## Canonical Local Development Baseline

- Canonical GitHub repository: `beyourlovercom/YBY-Core` on `main`
- Canonical Windows local Git checkout: `D:\ai\_repos\YBY-Core`
- Local Bottle site: `D:\ai\devybybottle` -> `https://localdev.ybybottle.com`
- Local Irrigation site: `D:\ai\devybyirrigation` -> `https://localdev.ybyirrigation.com`

After source changes, sync the canonical checkout to the local WordPress runtime with:

`powershell -ExecutionPolicy Bypass -File D:\ai\_system\sync-yby-core-local.ps1`

The runtime target is:

`D:\ai\devybybottle\app\public\wp-content\plugins\yby-core`

Run local browser or UAT checks only after the sync returns `YBY_CORE_LOCAL_SYNC=PASS`.

## Environment Deployment Targets

- Dev validation: `dev.ybyirrigation.com`
- Production: `ybyirrigation.com`

Dev is for environment-specific validation after Local PASS, consistent with `AGENTS.md`; it is not the default development environment.

## Credentials

Credentials must never be stored in:

- Git repository
- README
- Documentation
- Source code

Credentials are stored in:

- `.env.ybyirrigation.local`

## Required Variables

`WP_URL`

WordPress site URL.

`WP_USER`

WordPress API username.

`WP_APP_PASSWORD`

WordPress Application Password.

## WordPress Connection

Use:

- WordPress REST API

Authentication:

- Application Password

Example:

GET:

- `/wp-json/wp/v2/users/me`

Expected:

- Authenticated user response.

## Codex Usage

Before running WordPress integration tests:

Load:

- `.env.ybyirrigation.local`

Required:

- `WP_URL` exists
- `WP_USER` exists
- `WP_APP_PASSWORD` exists

## Security Rules

Never:

- Commit `.env` files
- Share Application Password publicly
- Put credentials into code

If credentials are exposed:

- Immediately revoke the Application Password.
