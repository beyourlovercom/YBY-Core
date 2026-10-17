# Andy Core v2.2.10 — signed Stable release (site deployment separately authorized)

## Scope of this release
- New **2.2.x** patch-series signed Stable release built from an immutable source tag on integrated YBY-Core main after PRs #159 and #160. Validate the exact source SHA in BUILD_INFO.md.
- The V2.2.10 PR changes only the version, signed build metadata/workflow, pinned inert Mail P9 regression harness and release documentation **relative to the integrated main**. The FINAL_RELEASE metadata patch changes no plugin runtime behavior.
- U3D verified actual Hostinger-unattended native WordPress signed install `2.2.8 → 2.2.9` on BYL Dev, including signature/ZIP verification, code backup, non-target plugin isolation and automatic OFF restoration.
- Before this release, public Stable/Latest was **v2.2.6**; public signed **v2.2.9** remains an immutable Canary Prerelease. Publishing this release does not automatically authorize installing it on sites.

## Stable-to-Stable scope must be separately audited
- A move from published Stable **v2.2.6 to v2.2.10 is NOT version-only**: the merged main includes previously integrated new features and fixes, including Creator read-only/history work and Hostinger scoped background updater. The V2.2.10 version-only PR does not modify those runtimes; their production exposure and permissions must nevertheless be reviewed.
- Existing domain and channel allowlists stay fail-closed: Production Stable sites require explicit per-site opt-in; BYL WWW remains outside the updater allowlist.
- Database compatibility marker `1.4.0` and runtime schema `1.5.0` remain unchanged; never assume this alone establishes production behavior safety.
- No Google Ads, inquiry/provider, payment, order, real mail or SMTP operations occur in candidate preparation.

## Signed release package acceptance
- Official PR GitHub CI on exact current **base+head merge** must pass, including PR #157 source SHA/provenance gate, backend regression, disposable WordPress/MySQL suites, 8-file signed release artifact, detached pinned Ed25519, SHA256, ZIP layout, correct plugin root/path and DB compatibility.
- A private PR candidate artifact is validation evidence only. The public eight-asset Stable Release must use **a distinct FINAL_RELEASE build** from the exact immutable source tag on the integrated main.

## Production safety gate
- Source PRs #159 and #160 are merged. Owner approval to publish a signed Stable release **does not permit site installation**.
- Verify production auto-updates remain OFF before publication; production Creator API defaults OFF, and real cross-account negative UAT remains an independent Owner gate.
- Before each site upgrade: obtain per-site authorization, take code-only rollback and plugin/core/theme baselines; verify Creator allowlist and website health, preserve business data. BYL WWW has its own independent gate.
