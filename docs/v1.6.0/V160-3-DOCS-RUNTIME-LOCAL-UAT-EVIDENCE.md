# V160-3 — Docs Runtime Local UAT Evidence

Date: 2026-09-17
Branch: `feature/andy-core-v1.6.0-docs-os-v1`
Baseline: `672aa3e26f07e6ddff1db26f2c69782cf2299e8e`
Canonical Local: `https://localdev.beyourlover.com`

## Scope

V160-3 implements a Shadow Native Docs Runtime behind `docs_os` without taking ownership of canonical BetterDocs URLs.

Surfaces validated:
- Docs Home
- Server-side Search
- Category
- Document Detail
- FAQ
- Tutorial

Runtime behaviors validated:
- TOC
- Related Docs fallback/mapping
- Preview-only JSON-LD schema
- Responsive layout
- Canonical BetterDocs coexistence
## Gate results

Focused harnesses:
- `module-registry-harness` PASS
- `module-boot-gate-harness` PASS
- `docs-os-module-shell-harness` PASS
- `docs-os-runtime-harness` PASS
- `git diff --check` PASS

HTTP Local UAT:
- Home 200
- Search 200
- Category 200
- Document 200
- FAQ 200
- Tutorial 200
- Existing BetterDocs canonical document 200

Data-retention snapshot before/after V160-3 UAT:
- docs: 29 → 29
- docs postmeta: 481 → 481
- docs taxonomy relationships: 51 → 51
- original module option restored after technical UAT

Visual UAT:
- 1440 desktop layout reviewed
- 768 tablet layout reviewed
- 390 mobile overflow issue found and fixed before closure
- empty TOC shell now suppressed when no headings exist
## Ownership boundary

V160-3 does not register canonical rewrite rules and does not use `template_include` takeover. BetterDocs remains the owner of existing `/docs/...` and taxonomy URLs until V160-4 migration reconciliation explicitly changes ownership.

Preview surfaces are Local/UAT-only and marked noindex. Docs data is reused in place; V160-3 performs no destructive migration.

## Exit

Technical Local UAT gate: PASS.
Owner visual UAT: READY.
Next stage after Owner PASS: V160-4 BetterDocs Migration Bridge.
