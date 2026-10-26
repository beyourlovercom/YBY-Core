# Andy Commerce · Marketing OS independent development lane — 2026-10-26

## Owner correction (authoritative)
Two ChatGPT windows must **develop concurrently by module**, not consolidate all implementation in one chat:
- This window exclusively develops **Marketing OS** (six campaigns, campaign selection/activation, promotion policies, gifts, BOGO/Bundle SKU allocations, Woo marketing-specific cart/checkout and order/quota/cancellation/refund business logic).
- The other window exclusively develops **Currency OS** (currency selector, exchange-rate computation, display, currency-specific Woo integrations).
- **Single integration and Dev publisher remains Draft PR #213** `feat/marketing-m10-policy-m9-bridge-dev-20261023`, not a shared editable development worktree. Its current currency/runtime commits are not to be overwritten by this lane.

## Branch and file ownership
- This isolated Marketing branch: `feat/marketing-os-independent-lane-20261026`. Origin snapshot of #213: `32824934420306a2880928a20e6f1f4c097f1d10`. The origin SHA is a snapshot, **not** the forever-current #213 HEAD.
- This branch may author Marketing-specific files under `packages/andy-commerce/includes/class-andy-commerce-m*.php`, Marketing admin views/assets, Marketing-only tests and docs; review each changed path for owner conflicts before committing.
- Currency-only source, `class-andy-commerce-currency-*.php`, Currency frontend assets and Currency-owned tests are **read-only here**.
- Shared plugin bootstrap/controller, `AGENTS.md`, ZIP/release scripts, central menus, version metadata, Woo currency/price hooks and integration wiring are **integration-owner gated**, not written concurrently by the two windows.
- Keep independent worktrees and commits; the #213 integrator shall review file-level diff, update its source from current exact branch heads, resolve shared wiring once, run complete Currency + Marketing regression and Dev ZIP contract. Neither module lane installs a Dev ZIP.
- Do not rebase forcefully onto moving #213 while it is being edited by Currency window. Deliver isolated commits with exact source/head and integration notes.

## Functional starting baseline
- M10–M12 six-activity rules, read-only Woo cart and six-type shadow already have CI/source proofs.
- M14 automatic six-promotion Woo hook evidence is **isolated sandbox MU-only**; it is **not** already active in the production Commerce runtime.
- M15 native SKU-specific attribution and quota ledger, M16 persisted Woo order/quota recovery have isolated test proofs, but full lifecycle hooks/TTL/per-customer policy are not yet active in production.
- New Marketing development must **reuse M10–M16**, not create duplicate rule engines; activity toggles remain OFF unless separately authorized for a bounded Dev-only test.
- Currency OS runs in the sibling lane and must be preserved; do not add a conflicting price/currency conversion layer from Marketing.

## Work sequence in THIS window (no duplication)
1. Review #213 exact-head Marketing classes and implement narrowly bounded Marketing-only business improvements/tests in isolated branch.
2. Verify Marketing six-type rules, customer coupon precedence, BOGO/Bundle SKU allocation, gift guard, campaign quota/idempotency and Woo status lifecycle in disposable offline Woo sandbox or pure tests as needed.
3. Submit Marketing-only commits to the dedicated Draft PR targeting #213; full code integration and plugin ZIP build belong solely to #213 integrator.
4. Provide Owner with verified results and explicit unresolved business-policy questions; hand off exact commits for sequential integration, never override Currency changes.

## Payment and safety
Owner waived **Stripe-specific provider sandbox testing**: `WAIVED — Owner Approved` is not a technical PASS. **Woo marketing math / promotions / order states / cancellation/refund logic tests remain mandatory.**
No main merge, Stable/WWW, Live Stripe, customer orders/emails/shipment/ERP, or READY device restart.
Historical P6 Stripe-only Draft PR #225 and Issue #216 remain closed as *P6-specific* work items; **their closure does not mean this Marketing OS development lane is closed.**
