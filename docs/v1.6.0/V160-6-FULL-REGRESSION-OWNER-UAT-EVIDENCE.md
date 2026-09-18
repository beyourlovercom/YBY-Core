# V160-6 — Full Regression / Owner UAT Evidence

Date: 2026-09-18
Scope: BYL Local canonical takeover
Status: Technical regression PASS; Owner UAT ready

## Functional regression

- `/docs/` — HTTP 200, Docs OS canonical renderer, zero preview links.
- `/docs-category/affiliate/` — HTTP 200.
- `/docs-category/shipping/` — HTTP 200.
- `/docs/affiliate/getting-started/` — HTTP 200.
- `/docs/shipping/how-to-track-my-order/` — HTTP 200, TOC path verified.
- `/docs/payment/discount-code/` — HTTP 200, mapped Related Docs verified.
- `/docs/shipping/i-havent-received-my-order/` — HTTP 200, mapped Related Docs verified.
- `/docs/how-to-delete-my-account/` — HTTP 200, uncategorized canonical route verified.

## Isolation / rollback

- Canonical ON: `yby-docs-page = true`.
- Canonical OFF: `yby-docs-page = false`; BetterDocs immediately resumes rendering.
- Canonical re-enabled after rollback test.
- Canonical pages contain zero `yby_docs_preview=` links.
## Responsive regression

Real Chrome screenshots were generated at 1440, 768 and 390 widths using the canonical `how-to-track-my-order` document.

- 1440: desktop TOC + article two-column layout renders normally.
- 768: responsive layout remains readable and contained.
- 390: mobile single-column fallback renders without the former narrow-column compression.

## Data-retention gate

Before and after canonical ON/OFF/ON regression:

- Docs: **29**
- Postmeta: **481**
- Term relationships: **51**
- Identity fingerprint: `30f608aae0601210cbcf393e2bc0c389f610f25c2a61afffbf1b77dbc2578a48`
- Fingerprint unchanged.

## Harnesses

- `module-registry-harness` PASS
- `module-boot-gate-harness` PASS
- `docs-os-module-shell-harness` PASS
- `docs-os-runtime-harness` PASS
- `docs-os-canonical-harness` PASS
- `git diff --check` PASS

V160-6 technical exit gate: **PASS**.
