# Andy Commerce · Single Dev Publisher (M10)
Applies to every Currency, Marketing and Commerce-plugin package change. Root AGENTS.md takes precedence.

## Ownership, immutability and source of truth
- Repository: `beyourlovercom/YBY-Core`. GitHub is the only authoritative source.
- Exclusive Dev publisher: **Draft PR #213 integration branch** `feat/marketing-m10-policy-m9-bridge-dev-20261023`. Only that **exact remote HEAD** may be prepared for Dev installation by `scripts/prepare-andy-commerce-dev-release.sh`.
- PR #207 Currency C2/C3 owns source development and independent CI only. Do NOT publish its Currency-only or stale Commerce ZIP on Dev. Other legacy Marketing PRs likewise may not install old ZIPs.
- Preserve Andy Core controlled L3 registry without editing Core. Exactly one Currency menu entry under Core/Commerce and five Marketing tertiary entries; six Currency tabs and USD-first five-currency Footer-only selector; all six Marketing draft/simulation types stay OFF.
- Release owner reconciles **latest exact Currency PR #207 HEAD** into the unified Commerce source using file-level review. No wholesale older package/source overwrite and no old same-version ZIP install.

## Promotion gate (preview != installable)
1. Lock exact PR #213 and #207 remote HEADs, confirm clean integration worktree. Plain `build-andy-commerce-dev-candidate.sh` is CI/local **preview**, never by itself install authorization.
2. Run `scripts/prepare-andy-commerce-dev-release.sh /path/to/candidate.zip`. It requires latest canonical remote HEAD, the newest Currency files byte-for-byte, frozen Core unchanged, both Marketing and Currency regression tests and the actual ZIP L3 contract.
3. Archive old known-good Dev Commerce package/installed controller hash without touching live orders; install only that one checked ZIP through supported authorized Dev WordPress path. No Chrome permission workaround or READY device restart.
4. Read back installed Commerce controller SHA256 and report ZIP SHA256, exact HEAD, six Currency tabs, one Currency L3, five Marketing L3, Footer-only and six actual Dev activity save/reopen/copy/archive/virtual lifecycle simulations. Confirm no live hooks, real rewards or Woo writes.
5. Restricted-role negative UAT Issue #208 remains separately evidence-based. No real transaction until P0–P4 fully PASS and separately scoped P5/P6 sandbox approval. No main/Stable/WWW deployment without separate authorization.

The gate does not revoke permissions held by existing WordPress users; it is enforced in source packaging and release workflow and recorded in both PRs.

## Owner 2026-10-28: Marketing V1 is frozen; Commerce → Campaign exit gate
- PR #213 stays the **only Currency + Commerce Dev publisher**. This child branch adds **source-only, reciprocal legacy Marketing runtime vetoes**; it must not publish its own Dev ZIP or replace PR #213's current Currency/Stripe files.
- **Do not add new Marketing V1 activities or extend M7–M10 no-code rule editing.** Preserve old Dev preview UI and pure tests as regression evidence until separately approved retirement; do not erase drafts or M15/M16 tables/claims.
- New seasonal campaigns belong to standalone Andy Campaign PHP Packs. When `andy_campaign_lite_master=yes`, M-P1 `current()` and M-P5 cap callbacks must reject legacy execution, and the legacy operator `arm` action must HTTP 409. When old STATUS or SWITCH is ON, Andy Campaign rejects itself. If both are ON, **both engines must fail closed**.
- Currency, Stripe, payment, order, taxes, shipping remain owned by Commerce; existing Andy Core Landing Pages editor and native menus remain owned by Core.
- No automatic DB migrations, option resets, Production writes or 'ready to merge' claims from a passing PHP harness. Integrate the guard only through the current exact-head Dev publisher and explicitly approved gate.
