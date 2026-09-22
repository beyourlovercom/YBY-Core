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


## 2026-09-22 remediation audit — existing-account staging search

The isolation gate was re-audited before any Dev deployment.

GitHub canonical state remained unchanged at the start of the audit:

- PR #47: open Draft, not merged
- branch head: `d22ac44107e98071576a1a90030c1c8335bb07cc`
- base/main: `0d556c4fd1d0a518a553fa20437a4b51754f9bc4`
- branch relation: 15 commits ahead / 0 behind
- Release Package Validation #73: success
- Andy Core Regression #124: success
- Inquiry Inbox WordPress MySQL Validation #105: success

A fresh read-only filesystem and WordPress identity sweep was performed across the Hostinger account.

Results:

- no new `dev.ybyirrigation.com` WordPress root exists
- the only YBY Irrigation root remains `/home/u595431186/domains/ybyirrigation.com/public_html`
- a number of separate `*.hostingersite.com` WordPress installations exist with their own stored `home/siteurl`
- those discovered Hostinger-site installations identify as unrelated/default WordPress sites (for example `My WordPress`, `biomedimplant`, `kumartedavisi`) and use Blocksy or Hostinger AI themes
- none of the inspected Hostinger-site installations contains Andy Core
- therefore none can be treated as the YBY Irrigation staging environment or overwritten/re-purposed without separate ownership confirmation

Decision remains:

**DEV UAT DEPLOYMENT: BLOCKED**

Required next remediation is to create a new Hostinger staging instance from hPanel for YBY Irrigation, or to provision a new explicitly isolated WordPress docroot + database. The resulting environment must satisfy the isolation proof listed above before the v1.7.0 RC can be deployed.


## Canonical Dev SSH details — 2026-09-22

Owner-confirmed SSH connection details for the real shared Dev host used by both `dev.ybyirrigation.com` and `dev.ybybottle.com`:

- Host/IP: `193.46.197.115`
- Port: `65002`
- Username: `u686797605`

Hostinger SSH key entry name observed in hPanel:

- `Svakom@2024`
- created: `2026-07-10`

Security note:

- password/private key material is not recorded in this repository
- the existing local SSH alias `yby-irrigation-dev` still points to the historical `194.164.64.172 / u595431186` target and must not be treated as canonical until updated
- isolation verification must be performed against `193.46.197.115:65002` with username `u686797605`


## 2026-09-22 canonical correction — real Dev host recovered

The earlier BLOCKED conclusion above was based on the historical SSH alias `yby-irrigation-dev`, which still pointed to the old Hostinger target `194.164.64.172 / u595431186`. That audit did **not** inspect the current real Dev host and is superseded by the evidence below.

Owner-confirmed current Dev SSH:
- host: `193.46.197.115`
- port: `65002`
- user: `u686797605`

SSH authentication was restored from the Windows development machine using alias `yby-shared-dev`.

Read-only isolation proof on the real Dev host:

### YBY Irrigation Dev
- docroot: `/home/u686797605/domains/dev.ybyirrigation.com/public_html`
- stored home: `https://dev.ybyirrigation.com`
- stored siteurl: `https://dev.ybyirrigation.com`
- database: `u686797605_eHkKS`

### YBY Bottle Dev
- docroot: `/home/u686797605/domains/dev.ybybottle.com/public_html`
- stored home: `https://dev.ybybottle.com`
- stored siteurl: `https://dev.ybybottle.com`
- database: `u686797605_GWtSz`

The two Dev sites use distinct docroots and distinct databases.

**DEV ISOLATION GATE: PASS**

## v1.7.0 Dev UAT deployment — 2026-09-22

Frozen RC re-verified before upload:
- package: `andy-core-v1.7.0.zip`
- size: `306997 bytes`
- SHA-256: `c9890568457e77e3998cb80d633c1e5dc927567e0dfe763f7dc6141f7e0a2698`

The same SHA-256 was verified again on the Dev host before deployment.

Pre-deploy Dev state:
- Andy Core: `1.4.0`
- status: active

Rollback copy:
`/home/u686797605/yby-backups/andy-core-v1.4.0-pre-v1.7.0-20260922`

Post-deploy Dev state:
- Andy Core: `1.7.0`
- status: active
- plugin header: `1.7.0`
- `YBY_CORE_VERSION=1.7.0`
- runtime database contract: `1.5.0`
- updater compatibility database contract: `1.4.0`
- no v1.7.0 database migration required

Runtime checks:
- canonical `yby_landing_page` CPT registered
- canonical rewrite slug = `lp`
- canonical landing archive disabled
- `yby_article_toc_settings_v1` absent after upgrade, preserving default-OFF behavior
- homepage response contained zero `yby-article-toc` markers
- no Dev `wp-content/debug.log` fatal evidence was present
- legacy site CPT `landing_page` still contains 12 historical entries; canonical `yby_landing_page` currently contains 0. This is migration/compatibility context, not a v1.7.0 release blocker.

**V1.7.0 AUTOMATED DEV UAT: PASS**

Remaining release gates:
1. Owner Dev UAT / UI confirmation if required.
2. PR #47 merge approval.
3. Stable tag / GitHub Release.
4. Explicit Production deployment approval.
