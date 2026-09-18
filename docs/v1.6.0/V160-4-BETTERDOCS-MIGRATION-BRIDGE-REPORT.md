# V160-4 — BetterDocs Migration Bridge Dry-run

Date: 2026-09-18
Scope: BYL Local only
Mode: read-only reconciliation; zero WordPress data writes

## Result

- Total Docs: **29**
- Published: **28**
- Draft: **1**
- Preserve: **26**
- Map: **2**
- Review: **1**
- Transform: **0**
- Blocked: **0**

The dry-run exit gate is **PASS**. The single review item is non-blocking because canonical identity and current empty-content behavior can be preserved exactly.

## Special cases

- `14582 affiliate-guide` — **review**: published with empty `post_content`; preserve ID/slug/URL/status and do not invent content.
- `24484 discount-code` — **map**: non-empty BetterDocs Related Articles metadata.
- `24538 i-havent-received-my-order` — **map**: non-empty BetterDocs Related Articles metadata.
- `18990 shipping-info` — draft, uncategorized, empty content; **preserve** as draft.
- `34774 how-to-delete-my-account` — published uncategorized doc; **preserve** on `/docs/{slug}/`.
## Preservation findings

- Post IDs and slugs are preserved in place; no post recreation is required.
- V160-1 canonical URL manifest covers all 29 rows.
- Uncategorized canonical routing is already part of the Docs OS contract.
- BetterDocs attachment metadata is empty for all 29 docs.
- BetterDocs core metadata exists for all 29 docs.
- Reusable-block metadata exists on 27/29 docs; the two missing rows are the two empty-content documents.
- Rank Math analytic object IDs and SEO scores exist on all 29 docs.
- Existing taxonomy relationships stay in the database; no destructive remap is required.
- 27 docs contain Gutenberg block markup; two docs have empty content.
- Content contains 18 inline image tags and 36 hrefs; preserving `post_content` preserves those references.
- No BetterDocs shortcode dependency was found.

## Source-content warnings

Existing malformed or legacy links (including historical `mailto` forms and shorthand internal URLs) were detected. They predate Docs OS and are **not** silently corrected during migration. They are source-content cleanup work, not migration blockers.

## V160-5 handoff

Canonical migration may proceed behind a reversible Local-only ownership flag. BetterDocs remains installed during transition. Runtime retirement is a separate controlled gate.
