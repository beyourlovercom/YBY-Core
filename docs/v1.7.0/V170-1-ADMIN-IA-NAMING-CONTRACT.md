# V170-1 — Canonical Admin IA / Naming Contract

> **Superseded for top-level Content IA by V170-3.5.** The stable identifiers defined here remain valid, but `Andy Docs` is no longer a top-level WordPress menu. It is a product under the canonical `Andy Content` shell.

## Baseline

- source branch: `feature/andy-core-v1.7.0-foundation`
- baseline: `main@0d556c4fd1d0a518a553fa20437a4b51754f9bc4`
- v1.6.0 stable tag: `0d556c4fd1d0a518a553fa20437a4b51754f9bc4`

## Frozen top-level display names

- plugin shell: **Andy Core**
- docs product: **Andy Docs**
- future Andy-owned top-level products use the `Andy ...` display prefix

## Stable identifiers

Display labels are not routing identifiers.

### Andy Core

- display label: `Andy Core`
- stable menu slug: `yby-os`
- capability contract: unchanged

### Andy Docs

- display label: `Andy Docs`
- stable menu slug: `yby-docs-os`
- capability: `andy_core_settings_manage`
- Docs editor/list/directory/settings slugs: unchanged
- post type / taxonomies: unchanged
- settings URL continues to target `page=yby-docs-os`

## Scope boundary

V170-1 is a display / IA contract only.

It does **not** rename:

- internal PHP classes or function prefixes
- the `docs_os` module id
- Docs post type / taxonomies
- existing admin URLs
- saved options
- capabilities

## Existing menu audit

Inquiry OS, Email OS, Project Studio, Social Login and Connector are not independently renamed by this stage. No scope expansion is permitted without a separate compatibility decision.

## Reserved future name

`Andy Template` is reserved for the future Canonical Module System. v1.7.0 must not register a visible empty Andy Template menu.

## Validation

`tests/v170-admin-ia-naming-harness.php` freezes the display label / stable slug separation and fails if a visible Andy Template menu is introduced early.
