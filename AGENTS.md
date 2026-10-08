# Andy Core — Shared Plugin Agent Governance V2

## Mission, repository boundaries and site-specific authority
- Andy Core is **generic WordPress foundation code**. WooCommerce/B2C-specific business policy belongs in Andy Commerce, not Andy Core.
- GitHub is the only canonical source/history. Work in scoped branches/worktrees; review and test exact revisions. Never edit a WordPress-installed runtime copy as the authoritative source.
- **Environment workflows are site-specific**, not universal:
  - **BYL Website V3**: `dev.beyourlover.com` is the Owner-approved long-lived **new-site integration/development and Owner UAT environment**. `localdev.beyourlover.com` is optional isolation. No incremental installation on legacy `www.beyourlover.com`; its eventual full-site cutover requires explicit Owner approval.
  - **YBY Bottle / YBY Irrigation**: preserve established Windows Local-first workflows and local sync. Dev remains the environment-specific validation target for these sites until their Owner explicitly changes those projects' rules.
- For a task shared across sites, run appropriate **Core unit/compatibility tests** independent of the site, then verify on each relevant site's chosen integration environment.

## Autonomous approved-scope workflow
- In an Owner-approved bounded work package, investigate the relevant source, implement, run proportionate tests, fix failures, commit, push and prepare/update PR without repeated micro-approval.
- Choose the available implementation provider based on complexity and validated access, not by a hard-coded model nickname. Do not require Codex Luna for every small code or documentation fix.
- Follow required PR protection/checks, branch isolation and Owner-facing UAT. One work package/branch/PR and one active implementing agent per worktree; never parallel-edit the same file.
- Missing checks or failed tests are not PASS. Do not suppress failures or expand scope to make evidence look clean.

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
