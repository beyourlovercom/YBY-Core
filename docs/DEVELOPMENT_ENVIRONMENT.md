# YBY Core Development Environment

## Purpose

Define local environment configuration rules for YBY Core development.

## Environment Priority

Development:

- `dev.ybyirrigation.com`

Production:

- `ybyirrigation.com`

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
