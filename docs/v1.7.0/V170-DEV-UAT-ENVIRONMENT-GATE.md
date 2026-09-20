# Andy Core v1.7.0 — Dev UAT Environment Gate

Date: 2026-09-20

Branch:

`feature/andy-core-v1.7.0-foundation`

Current branch head before this evidence commit:

`21eda703926943f82bb73db5484ca33345fd1f0b`

## Intended Dev target

Repository development documentation identifies:

- Dev validation: `https://dev.ybyirrigation.com`
- Production: `https://ybyirrigation.com`

The repository also requires Local PASS before Dev UAT and a separate Production authorization after Dev UAT.

## Read-only environment audit

The configured SSH alias `yby-irrigation-dev` connects successfully to the Hostinger account.

Available tooling:

- WP-CLI: available
- PHP CLI: 8.3.33

The account contains multiple WordPress installations.

A read-only enumeration of all WordPress roots under the account found no installation whose stored WordPress `home` option is `dev.ybyirrigation.com`.

The only YBY Irrigation WordPress root found was:

`/home/u595431186/domains/ybyirrigation.com/public_html`

Its stored WordPress identity is:

- home: `https://ybyirrigation.com`
- siteurl: `https://ybyirrigation.com`
- front page: `26`
- theme: `bricks`
- installed Andy Core plugin header at audit time: `1.5.2`

This is the Production WordPress installation and must not be used as an isolated Dev deployment target.

## Why dev.ybyirrigation.com still renders

The Production WordPress root contains the Hostinger MU plugin:

`wp-content/mu-plugins/hostinger-preview-domain.php`

Plugin metadata states:

`Description: Enable access to the website through a temporary domain while the main domain is not yet configured.`

The implementation:

1. reads the current request `HTTP_HOST`
2. reads the stored site domain/database site URL
3. activates URL/content rewriting when current domain differs from site domain
4. filters `home_url`, `site_url`, redirects, assets, content and admin URLs
5. rewrites the Production site domain to the current preview host in rendered responses

The core gate is effectively:

`current_domain !== site_domain`

This directly explains why requests to `https://dev.ybyirrigation.com` can render links using the Dev hostname even though the underlying WordPress database identifies the site as `https://ybyirrigation.com`.

## DNS / access evidence

At audit time:

- `dev.ybyirrigation.com` resolved to a Hostinger address distinct from the public Cloudflare addresses returned for `ybyirrigation.com`
- the configured SSH alias is an account/server access endpoint and is not proof of a distinct Dev WordPress root
- no separate Dev WordPress `wp-config.php` / database identity was found in the account enumeration

## Gate decision

**DEV UAT DEPLOYMENT: BLOCKED**

Reason:

`dev.ybyirrigation.com` is currently acting as a Hostinger preview/alias surface for the Production WordPress installation rather than a verified isolated Dev WordPress environment.

Deploying the v1.7.0 RC into the only discovered YBY Irrigation plugin directory would therefore be a Production code write, not a Dev-only deployment.

No RC package was uploaded.

No remote plugin file was changed.

No remote WordPress option/database row was changed.

Production remains untouched.

## Required remediation before Dev UAT

One of the following must exist and be verified before the Dev UAT gate can reopen:

1. a separate WordPress installation for `dev.ybyirrigation.com` with its own docroot and database; or
2. another explicitly approved isolated staging WordPress instance.

Minimum isolation proof:

- distinct WordPress docroot
- distinct database identity or explicitly isolated staging database
- stored `home/siteurl` appropriate for the Dev/staging environment
- a deployment path that cannot overwrite `ybyirrigation.com/public_html`
- rollback/backup path for that isolated environment

A preview alias of the Production site is not sufficient.

## Current v1.7.0 state

Completed before this gate:

- Foundation implementation: PASS
- Landing Page CPT Recovery: PASS
- Article TOC V1 engineering: PASS
- Article TOC Local functional UAT: PASS
- Article TOC Owner UAT: PASS
- Full regression: PASS
- v1.7.0 RC identity: PASS
- RC package build: PASS
- RC package SHA/metadata validation: PASS
- v1.5.7 updater backward-compat validation against v1.7.0 RC: PASS

RC package:

`andy-core-v1.7.0.zip`

RC source commit:

`6fda70644ef3215c9ae480790ab18f1a9cb19013`

RC SHA-256:

`c9890568457e77e3998cb80d633c1e5dc927567e0dfe763f7dc6141f7e0a2698`

The Dev UAT environment gate is the current blocker. Stable tag, GitHub Release publication and Production deployment remain separate later gates.
