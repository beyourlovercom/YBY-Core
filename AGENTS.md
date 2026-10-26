# Andy Core — Shared Plugin Agent Governance V2

## Mission, repository boundaries and site-specific authority
- Andy Core is **generic WordPress foundation code**. WooCommerce/B2C-specific business policy belongs in Andy Commerce, not Andy Core.
- GitHub is the only canonical source/history. Work in scoped branches/worktrees; review and test exact revisions. Never edit a WordPress-installed runtime copy as the authoritative source.
- **Environment workflows are site-specific**, not universal:
  - **BYL Website V3**: `dev.beyourlover.com` is the Owner-approved long-lived **new-site integration/development and Owner UAT environment**. `localdev.beyourlover.com` is optional isolation. No incremental installation on legacy `www.beyourlover.com`; its eventual full-site cutover requires explicit Owner approval.
  - **YBY Bottle / YBY Irrigation**: preserve established Windows Local-first workflows and local sync. Dev remains the environment-specific validation target for these sites until their Owner explicitly changes those projects' rules.
- For a task shared across sites, run appropriate **Core unit/compatibility tests** independent of the site, then verify on each relevant site's chosen integration environment.

## Andy Commerce Dev release authority — Owner decision 2026-10-23
- **ONE Dev installer/publisher for the entire Andy Commerce plugin**: the integration lineage maintained in [Draft PR #213](https://github.com/beyourlovercom/YBY-Core/pull/213), ref `feat/marketing-m10-policy-m9-bridge-dev-20261023`. It owns the only installable Dev ZIP and rollback/readback evidence for `dev.beyourlover.com`.
- Currency C2/C3 [Draft PR #207](https://github.com/beyourlovercom/YBY-Core/pull/207) and other Commerce branches are **code-and-CI-only producers**. They may make source commits and local/CI preview ZIPs, but may not overwrite the Dev-installed Commerce plugin with independent/same-version ZIPs. Changes from #207 must be reconciled with the exact current HEAD of #213 before the next Dev integration install. No cherry-picking the whole older tree or removing Marketing/controlled L3 adapter to resolve conflicts.
- The designated integrator must use `scripts/prepare-andy-commerce-dev-release.sh` (verified exact GitHub HEAD + latest Currency source parity + all Commerce ZIP/function contracts + full regression) before labelling any artifact *Dev-installable*. Plain build output is **preview only**. Retain last known-good Dev rollback, record new SHA256 and installed-controller SHA256, confirm one Currency L3, six inner Currency tabs, five Marketing L3, Footer-only frontend selector and all six Dev simulations.
- This is a **source/process gate**, not a change to WordPress administrator permissions or a bypass of security checks. An administrator could technically install another ZIP, but doing so is outside authorized release ownership. No GitHub main/Stable/WWW change, no real order/payment/discount/gift/shipping/inventory/email write, and no READY device restart under this approval.
- Plugin-local details: [packages/andy-commerce/AGENTS.md](packages/andy-commerce/AGENTS.md).

## Autonomous approved-scope workflow
- In an Owner-approved bounded work package, investigate the relevant source, implement, run proportionate tests, fix failures, commit, push and prepare/update PR without repeated micro-approval.
- Choose the available implementation provider based on complexity and validated access, not by a hard-coded model nickname. Do not require Codex Luna for every small code or documentation fix.
- Follow required PR protection/checks, branch isolation and Owner-facing UAT. One work package/branch/PR and one active implementing agent per worktree; never parallel-edit the same file.
- Missing checks or failed tests are not PASS. Do not suppress failures or expand scope to make evidence look clean.

## Disposable test account and Owner UAT login readiness (Owner rule, 2026-10-26)
- For **temporary/isolated test accounts**, prepare the usable authenticated browser session and open the exact review page **before** asking Owner to perform UAT. Never hand off a blocked login form as a review-ready page.
- When authorized and technically permitted, set up test-only credentials locally, sign in using legitimate UI authentication on the designated READY device (without restarting its browser), verify the final page loads, and provide Owner with a ready-to-review page. Do not assume a `pass` message proves account login or OAuth completion; confirm from actual readback.
- If a temporary test username/password must be given to Owner, provide a usable credential handoff through an approved secure channel or a plainly stated accessible path. **Do not hide credentials yet tell Owner to test**; never commit passwords, tokens, cookies, or private keys to GitHub, logs or artifacts.
- A platform security block is a hard stop. Do not bypass it by changing tool, channel, or authentication strategy; instead report the precise blocker, keep the review status `BLOCKED`, and give a minimal safe Owner-only recovery action.
- For third-party OAuth, SSO, MFA, payments and consent, pre-open and validate as much of the authenticated test interface as permitted. Leave only the actual identity/consent action to the authorized human; keep Live and Production isolated.
- Owner UAT is PASS only when an accessible, correctly authenticated review page and the requested behavior have actually been verified.

## Hard stops
Stop for Owner authorization before WWW/Production deploy; real payment/email/send events; destructive/irreversible customer/order/financial data writes; permission/business-model/architecture changes beyond approved scope; owner-only access and secret/2FA actions; or explicit Owner design/UAT acceptance gates.
- Do not fabricate transactions, success statuses, synthetic Woo orders, or compatibility evidence.
- Do not commit DBs, uploads, caches, full WordPress site contents, secrets, machine-local data, credentials, test screenshots or production environment values.
- Any Dev integration write must have a verified target, rollback/backup and post-deploy validation. A merged PR is not a Production authorization.

## Windows baseline (when the target project uses Local)
- Repository: `D:\ai\_repos\YBY-Core`
- Bottle LocalWP: `D:\ai\devybybottle` / `https://localdev.ybybottle.com`
- Irrigation LocalWP: `D:\ai\devybyirrigation` / `https://localdev.ybyirrigation.com`
- For **Bottle Local runtime sync only**, use:
  `powershell -ExecutionPolicy Bypass -File D:\ai\_system\sync-yby-core-local.ps1`
- Run the relevant Local browser/UAT after successful sync. Never copy `.git`, documentation or tests into packaged runtime plugins.

## Context map — load only when pertinent
- [Site-specific environments and secrets](docs/DEVELOPMENT_ENVIRONMENT.md)
- [Branch/PR/release and code standards](docs/DEVELOPMENT_WORKFLOW.md)
- [Release workflow](docs/RELEASE_WORKFLOW.md)
- [BYL Website V3 agent policy](https://github.com/beyourlovercom/beyourlover-web-v3/blob/main/AGENTS.md) — authoritative for BYL's Dev-first integration and Owner UAT
- Core-specific addon/module contracts: inspect relevant `inc/`, `modules/` and tests as required, not every time by default.

When a shared document conflicts with a site-specific, newer Owner-approved rule, **apply the site-specific rule to that site's integration/UAT**; preserve universal security and production safeguards.
