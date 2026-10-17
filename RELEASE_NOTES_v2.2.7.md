# Andy Core v2.2.7 — signed Canary CANDIDATE (not published)

## Changes from Stable v2.2.6

- Reuse reviewed PR #153 Hostinger Smart Auto Updates (MU v1.0.8) WordPress background-update compatibility, gated by both the Andy Core signed-channel/hostname allowlist and per-site explicit auto-update opt-in.
- Register `automatic_updater_disabled` filter at priority 1000 to permit the normal WordPress background updater only when the site opts into Andy Core automatic updates.
- While the exception is active, allow WordPress automatic installation only for `yby-core/yby-core.php`; deny other plugins, Core, themes and translations.
- Preserve GitHub Ed25519 metadata signature + SHA256 + ZIP validation, code-only rollback, same-version stale-offer guards, Canary/Stable distribution separation, Chinese menu and addon boundaries.
- No changes to database schema, settings migration, WordPress business data, Mail OS worker, WooCommerce, checkout, payments or orders.

## Release gates — NOT authorized by this candidate

1. GitHub CI 4/4 must PASS against the exact 2.2.7 candidate HEAD, including reproducible package safety and signer/key verification.
2. Verify all 8 release assets and Ed25519 signature using current pinned public key; public GitHub Release must be created separately as **v2.2.7 Prerelease/Canary**, not Latest, only after explicit Owner authorization.
3. BYL Dev native WordPress WP-Cron UAT is proposed **after** verified public Canary v2.2.7 publication. Current BYL Dev 2.2.6 Canary + auto OFF is the proposed test baseline; this candidate does **not** change its configuration.
4. Bottle Dev must stay automatic update OFF. No GitHub main merge or Stable promotion, production website install or auto-policy change, BYL WWW modification, or READY device restart under this candidate.
5. Root provenance: PR #153 source code `af2a235337a6acb285bea223081571c8fe5a41c4`, new versioned candidate branch will receive a separate HEAD; do not re-sign or replace immutable Stable v2.2.6 artifacts.
