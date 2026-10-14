# Andy Core Business OS V1 — B1 Admin Shell

Related: [Issue #89](https://github.com/beyourlovercom/YBY-Core/issues/89).

## Owner gates
B0 interactive admin prototype: Owner PASS (2026-10-14).
B1 implementation: isolated source branch and PR only. No merge to main, no Dev/WWW deployment, no real B2B data.

## Scope shipped in B1 branch
- New optional Registry V2 extension \`business_os\`, default OFF, site-gated to BYL trusted identity or exact approved BYL host.
- New WordPress admin **Business** first-level menu with Overview, Applications, Partnership Programs, Business Partners, Catalog & Pricing, Fulfillment, Business Pages and Settings.
- Static read-only six-program vocabulary, no fictitious live metrics, no ERP connectivity and no application submissions.
- Inquiry OS link reused only when the existing Inquiry module and lead access permit it.
- Admin style isolated to Business admin screens. BYL wine-red accents; mobile responsive.
- Permission checks on menu registration **and** direct route rendering, using existing \`andy_core_settings_manage\` permission. No role/capability data migration or mutations.
- No scripts or assets injected on B2C frontend, no public hooks.

## Explicitly deferred
B2: frontend Business pages and partner UI, visual UAT.
B3: application form, field mapping, anti-spam, ERP connector with explicit contract and fail-closed idempotence.
B4: pricing/quotes/PI/partners, ERP authority only.
B5: dropshipping canary after inventory, shipping, packaging and tax rules.
B6: partner portal after an independent approval.

## Verification
\`php -l inc/class-yby-business-os-module.php\`
\`php -l admin/class-yby-business-os-admin.php\`
\`php tests/business-os-b1-shell-harness.php\`

The standard \`Andy Core Regression\` workflow runs all PHP harnesses on pull requests. The B1 harness verifies off-by-default, exact-site eligibility, no auto-persistence, registration once, admin role gate, 403 direct-access gate, eight menu entries, six partnership programs, BYL-only branding and no live data.

## Release governance
- GitHub main is the source of truth; no runtime patching.
- B1 PR must remain open for review; not merged or deployed without the next Owner gate.
- Before any future BYL Dev integration, validate exact commit, backup/rollback and existing Cart/Checkout/Notifications regression.
