# Andy Campaign Lite — package scope (2026-10-27)

Root /AGENTS.md remains authoritative.

- This is a **code-first registry and safety gateway**, NOT a new ten-type no-code promotion builder.
- Andy Core alone registers and edits `landing_page` CPT and composes the WordPress L2/L3 menu. Do not create pages, CPTs, content editors, shadow page copies, or a top-level WP menu here.
- Packs link only to verified, existing `landing_page` IDs. Page changes happen in the original Andy Core-owned WordPress editor.
- Andy Commerce owns Currency OS, checkout/payment infrastructure and shipping policy. Do not call legacy Dev marketing adapters directly or import them as production runtime.
- Trust only filesystem-installed, reviewed PHP packs. No wp-admin PHP textarea/eval/remote code retrieval. Packs call `andy_campaign_register` and register hooks **only inside** their host-gated boot callback.
- All packs default OFF, the global master defaults OFF, conflicting declared resource groups refuse simultaneous execution, and legacy Andy Commerce Dev-UAT marketing activation excludes Campaign runtime.
- The pack callback is trusted executable PHP, not a sandbox. Core can stop registered hooks from being attached, but cannot neutralize malicious/incorrect pack top-level code or hooks attached outside the contract.
- No order, customer, stock, currency, shipping or payment writes in this C0–C2 iteration. Administrator event logs are metadata only, NOT proof of transaction settlement.
- CI includes PHP lint and focused decision-contract tests. Dev UAT, WordPress native menu readback, rollback and production launch are separate owner-gated stages.
- Admin UX is desktop-only. All future shopper-facing pack components remain mobile-capable.
