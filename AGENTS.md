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

## Andy Campaign Code-First architecture — Owner decision 2026-10-27
- The marketing product direction is **Code-First**: seasonal/novel business rules ship as independently versioned, reviewed PHP Campaign Packs. Do not extend Andy Commerce or the Campaign kernel for each holiday. New common kernel APIs require a separate compatibility review.
- **Andy Core alone owns** registration, editing and native WordPress L2/L3 navigation for `landing_page` CPT. Andy Campaign MUST NOT register a competing landing-page CPT, editor, list or content store. It may reference existing landing-page IDs, validate them and link to Andy Core-owned editor routes. Never move, duplicate or migrate landing-page records.
- **Andy Commerce owns** Currency OS and Woo transaction primitives; Andy Campaign owns only campaign package registration, configuration, scheduling, execution gating, activation/audit and narrow shared campaign services. Never copy Currency, checkout or Shipping runtimes into Andy Campaign.
- **Andy Core owns menu composition**. Andy Campaign supplies its own authorized controller and, when installed, takes the existing marketing L2 destination through the Core navigation adapter; do not add a duplicate WordPress top-level menu. The existing Content/Landing Pages navigation remains unchanged.
- Campaign Packs are vetted filesystem PHP/WordPress packages, not PHP submitted in a WP admin textarea or arbitrary uploaded scripts. A pack must register through the documented host contract, refrain from independent pricing hooks outside the host activation gate, and store its own tests/assets. Disabling the campaign must stop its future hooks, not remove completed orders or their metadata. PHP packs share the WordPress process; a kill switch is not an isolation sandbox.
- Baseline is a separate **Draft branch/PR**. Existing Andy Commerce Marketing V1 Dev-UAT and Currency integration PR #213 are not implicitly merged, migrated, enabled or replaced; legacy Marketing remains OFF. Do not deploy to Dev/main/Stable/WWW or touch real customers, payments, mail or stock without the separately scoped owner-approved release gate. Preserve rollback versions and dual-engine exclusion before any later production transition.
- The admin controls are **desktop-only**. Customer-facing landing pages, gift pickers, cart/checkout content and promotional UI must remain usable on desktop and mobile.

## Marketing OS V1 selective retirement — Owner decision 2026-10-28
- **No new Marketing V1 features in Andy Commerce.** M7/M9/M10 dashboards, generic six-type simulators and Dev sandbox remain **OFF/archived compatibility evidence**, not the future runtime or authoring UI. Do not port their entire codebase or database into Andy Campaign.
- **One marketing runtime authority:** Andy Campaign is the sole new activity orchestrator; each new promotion is a separately reviewed PHP Campaign Pack. The legacy Commerce M-P1/P2/P5 runtime and Campaign kernel must never both run; both directions independently reject activation/execution while the opposing master/sandbox switch is ON. A torn/both-ON state must fail closed (no discount application); never silently reset either switch or delete historical records.
- **Currency release independence:** PR #213 is still the sole Andy Commerce Dev publisher, and its Currency/Stripe/Shipping changes must not be merged, rewritten, removed or repackaged as part of the Campaign migration. Legacy Commerce mutual-exclusion compatibility goes through a separate source-only child review; any eventual Commerce ZIP change must follow its own exact-head publisher gate.
- **Selective reuse only on demand:** M11 cart read patterns, M15 quota ledger and cent-allocation, M16 durable order reconcile may be extracted to neutral Campaign shared services only after schema ownership, USD/multicurrency, HPOS, refunds and order persistence gates. No automated M15 table migration or renaming during C3–C5. Preserve old M15 schema and claims untouched until explicit approved cutover/rollback.
- **Menu:** Andy Core owns native Landing Pages and unified navigation. Andy Campaign owns Marketing L2 when present. Andy Commerce may retain a read-only legacy Dev UAT route for evidence but must not register a competing customer-facing marketing executor.
