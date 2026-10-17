# Andy Core v2.2.10 — U4 signed Stable candidate (PRIVATE, not published)

## What this candidate is
- New **2.2.x** patch-series signed candidate built from integrated YBY-Core GitHub main after [PR #159](https://github.com/beyourlovercom/YBY-Core/pull/159); main base SHA `a580f97b7e1f324b684d15caf0ff179dfaba7781`.
- The V2.2.10 PR changes only the version, signed build metadata/workflow, pinned inert Mail P9 regression harness and release documentation **relative to that main**.
- U3D verified actual Hostinger-unattended native WordPress signed install `2.2.8 → 2.2.9` on BYL Dev, including signature/ZIP verification, code backup, non-target plugin isolation and automatic OFF restoration.
- Current public Stable/Latest remains **v2.2.6**; public signed **v2.2.9** remains an immutable Canary Prerelease. **No 2.2.10 public release exists yet.**

## Stable-to-Stable scope must be separately audited
- A move from published Stable **v2.2.6 to v2.2.10 is NOT version-only**: the merged main includes previously integrated new features and fixes, including Creator read-only/history work and Hostinger scoped background updater. The V2.2.10 version-only PR does not modify those runtimes; their production exposure and permissions must nevertheless be reviewed.
- Existing domain and channel allowlists stay fail-closed: Production Stable sites require explicit per-site opt-in; BYL WWW remains outside the updater allowlist.
- Database compatibility marker `1.4.0` and runtime schema `1.5.0` remain unchanged; never assume this alone establishes production behavior safety.
- No Google Ads, inquiry/provider, payment, order, real mail or SMTP operations occur in candidate preparation.

## Signed release package acceptance
- Official PR GitHub CI on exact current **base+head merge** must pass, including PR #157 source SHA/provenance gate, backend regression, disposable WordPress/MySQL suites, 8-file signed release artifact, detached pinned Ed25519, SHA256, ZIP layout, correct plugin root/path and DB compatibility.
- Candidate GitHub Actions artifact is **private validation evidence**, not a public `v2.2.10` Release; no new git tag or Stable/Latest promotion in this phase.

## Separate Owner approval required
1. Review main-vs-Stable `v2.2.6 → v2.2.10` behavior and ensure no unrelated integrated module becomes unexpectedly enabled on Production; obtain Owner code/visual UAT if needed.
2. Authorize PR exact-head merge to main separately after 4/4 CI, maintaining Creator parallel branches and release-provenance rules.
3. Authorize **new signed immutable v2.2.10 Stable publication** separately; keep the current v2.2.6 Latest until that gate is approved.
4. Authorize Production deployments and per-site automatic-update rollout separately, with rollback/monitoring. No BYL WWW deployment without its own gate.
