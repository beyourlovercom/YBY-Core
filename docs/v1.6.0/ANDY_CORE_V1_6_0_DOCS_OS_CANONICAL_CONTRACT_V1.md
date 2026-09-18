# Andy Core v1.6.0 — Docs OS Canonical Contract V1

Status: V160-1 contract draft from Local canonical inventory
Canonical baseline: 672aa3e26f07e6ddff1db26f2c69782cf2299e8e
Canonical Local site: localdev.beyourlover.com

## 1. Scope

Docs OS V1 is the first feature module designed natively for Andy Core Modular Runtime V1. It owns runtime, presentation, search, TOC, related docs, schema, module settings, assets, hooks and routes. Site content remains in the site's WordPress database.

## 2. Module lifecycle contract

- Module key: docs_os.
- Existing sites: default OFF until explicitly enabled.
- OFF MUST hide Docs OS menus and stop Docs runtime, Docs assets, Docs hooks and Docs routes.
- OFF MUST NOT delete or mutate existing docs posts, terms, options, media or metadata.
- Re-enable MUST restore access to prior configuration and data.

## 3. Canonical BYL source inventory

Inventory source: local WordPress database for localdev.beyourlover.com.

- BetterDocs plugin installed: etterdocs.
- BetterDocs Pro installed: etterdocs-pro.
- BetterDocs post type currently in use: docs.
- docs rows: 29 total.
- Published docs: 28.
- Draft docs: 1 (shipping-info, post ID 18990).
- doc_category: 7 terms.
- doc_tag: 2 terms.
- etterdocs_faq_category: present.
- Site permalink structure: /blog/%postname%/ (Docs runtime must independently preserve current Docs public URL behavior rather than infer from general post permalink structure).

Current category hierarchy includes:
- Affiliate (ffiliate) — 6 docs.
- App (pp) — 1 doc.
- Sex Toys (sex-toys) — 0 docs.
- Support (support) — 0 docs.
  - Points (points) — child of Support, 1 doc.
- Shipping (shipping) — 7 docs.
  - Payment (payment) — child of Shipping, 11 docs.

Current document metadata observed on all 29 docs includes:
- _betterdocs_attachments
- _betterdocs_est_reading_text
- _betterdocs_meta_views
- _betterdocs_related_articles
- _edit_last
- _edit_lock
-
ank_math_analytic_object_id
-
ank_math_seo_score

Also observed on subsets:
- _betterdocs_reusable_block_ids
- Rank Math OG/primary/internal-link metadata
- WPCode page/body/header/footer metadata
- WPML media/location/word-count metadata
- AffiliateWP submission metadata
- footnotes and other plugin metadata

Docs OS migration MUST preserve unknown/non-owned metadata by default unless a specific transform is documented.

## 4. Identity preservation contract

Migration is reconciliation, not destructive re-import.

Priority order:
1. preserve Post ID;
2. preserve slug;
3. preserve public URL;
4. preserve category/tag relationships and hierarchy;
5. preserve SEO metadata;
6. preserve media and attachments;
7. preserve article identity;
8. preserve internal links;
9. preserve search-indexable content;
10. avoid redirects unless unavoidable.

The bridge MUST NOT delete and recreate existing docs posts merely to move ownership from BetterDocs runtime to Docs OS runtime.

## 5. Content/storage boundary

V1 canonical strategy:
- Keep existing docs posts as the primary content records for BYL migration unless a later bounded contract proves an in-place post-type transition is required and safe.
- Preserve existing terms and term relationships.
- Preserve existing postmeta unless explicitly mapped/transformed.
- BetterDocs may remain installed during Local migration verification while Docs OS progressively assumes runtime responsibilities.
- BetterDocs runtime retirement is a separate controlled gate from data preservation.

## 6. Required migration bridge classifications

Every source record/change MUST classify as one of:
- preserve
- map
- 	ransform
-
eview
- locked

Dry-run output MUST include source/target counts, Post ID result, slug diff, URL diff, taxonomy diff, SEO meta result, media/link result and unresolved exceptions.

## 7. URL and SEO contract

Before Local apply, the bridge MUST capture the current public URL for every published doc and compare it with the Docs OS candidate URL.

Hard gate:
- no silent URL drift;
- no silent slug drift;
- no silent canonical/schema ownership collision;
- Rank Math metadata remains attached to the same Post IDs where technically possible.

Any unavoidable URL change requires an explicit exception manifest before apply.

## 8. Runtime contract

Docs OS V1 runtime must provide:
- Docs Home;
- Category archive;
- Document detail;
- FAQ rendering;
- Tutorial structure;
- search;
- TOC;
- Related Docs;
- schema/structured data;
- responsive desktop/tablet/mobile behavior;
- reusable design tokens and site-brand compatibility.

Runtime must not assume BYL-only categories, copy or URLs.

## 9. Search / TOC / Related / Schema ownership

These behaviors are migration-sensitive and must be inventoried against current BetterDocs behavior before replacement is activated. Until equivalence is validated, Docs OS must not disable the corresponding BetterDocs runtime behavior on Local.

Related-docs source data currently exists in _betterdocs_related_articles on all 29 docs and must be preserved or explicitly mapped.

## 10. Roles and capabilities

V160-1 must inventory BetterDocs editing/view capabilities before Docs OS registers its final capabilities. Docs OS must avoid broadening privileged access accidentally. Existing authors/editors must not lose access merely because runtime ownership changes.

## 11. Shortcodes / blocks / reusable content

V160-1 must detect BetterDocs-specific shortcodes, blocks and reusable-block references in the 29 docs. _betterdocs_reusable_block_ids exists on 27 docs, so reusable content is a mandatory migration check.

## 12. V160-1 exit checklist

Before implementation begins:
- [x] Post type identified.
- [x] Taxonomies and hierarchy inventoried.
- [x] Post IDs/slugs/statuses inventoried.
- [x] Core BetterDocs/SEO metadata families inventoried.
- [x] Non-destructive identity rules frozen.
- [x] Exact current public URL manifest captured.
- [x] BetterDocs search behavior inventoried.
- [x] BetterDocs TOC behavior inventoried.
- [x] FAQ/Tutorial source shapes inventoried.
- [x] Roles/capabilities inventoried.
- [x] Frontend template/runtime ownership inventoried.
- [x] BetterDocs-specific shortcode/block usage inventoried.
- [x] Schema ownership/collision behavior inventoried.

## 13. Implementation gate

V160-2 Module Shell may begin only after the remaining V160-1 inventory items above are captured and any migration-impacting exception is written into this contract or an attached evidence report.


## 14. Frozen Local runtime findings

- BetterDocs settings: builtin_doc_page=true, docs_slug=docs, category_slug=docs-category, tag_slug=docs-tag, permalink_structure=docs/%doc_category%, enable_category_hierarchy_slugs=false.
- Exact URL manifest: `BYL_BETTERDOCS_URL_MANIFEST_V1.tsv` (29 rows). Categorized docs resolve as `/docs/{category}/{slug}/`; uncategorized docs resolve as `/docs/{slug}/`.
- Verified HTTP 200 examples: `/docs/affiliate/getting-started/`, `/docs/shipping/how-to-track-my-order/`, `/docs-category/shipping/`. The synthetic `/docs/uncategorized/how-to-delete-my-account/` redirects to `/docs/how-to-delete-my-account/`.
- Search: BetterDocs live_search=true, advance_search=true, minimum 3 characters, placeholder `Search...`, no-result text configured; Docs OS must preserve search scope/content behavior before BetterDocs search is retired.
- TOC: enable_toc=true, hierarchy=true, sticky=true, mobile collapsible=true, heading tags H1-H6. Docs OS must preserve these user-visible semantics.
- Related Docs: show_related_docs=true and `_betterdocs_related_articles` exists on all 29 docs; preserve/map this field in place.
- FAQ schema: enable_faq_schema=true. BetterDocs FAQ rendering delegates to BetterDocs FAQ shortcodes and attaches FAQ schema when enabled. No `betterdocs_faq` posts are present in this Local DB; `betterdocs_faq_category` has one term. Existing unrelated `helpie_faq` has 3 published posts and is not to be silently reclassified as Docs OS data.
- Tutorial: no separate tutorial post type/data model was found. Current tutorial semantics are ordinary `docs` content; `artist-collaboration-tutorial` is a published docs record. Therefore V1 treats Tutorial as presentation/content semantics over docs rather than destructive type migration.
- Roles: article_roles = administrator, editor, shop_manager; settings_roles = administrator; analytics_roles = administrator, editor, shop_manager; faq_roles = administrator, shop_manager, editor. Docs post type uses capability_type `[doc, docs]`, map_meta_cap=true; doc taxonomies use manage/edit/delete_doc_terms and edit_docs assignment.
- Frontend ownership currently includes post type registration/rewrite, taxonomy registration/rewrite, BetterDocs template loader, archive/category/single runtime, assets, REST endpoints, search, TOC, related docs, FAQ rendering/schema and frontend hooks. Docs OS may replace these only behind its Boot Gate.
- Shortcode/block scan across the 29 docs found zero inline `[betterdocs_...]` shortcodes and zero `wp:betterdocs/` blocks. However `_betterdocs_reusable_block_ids` exists on 27 docs, so reusable-block references remain a migration preservation check.
- Schema collision rule: Docs OS must not emit duplicate FAQ/article/breadcrumb schema while BetterDocs or Rank Math is still emitting equivalent schema. Runtime ownership transition must be mutually exclusive per surface.

## 15. V160-1 Exit Gate

Status: PASS for implementation entry. The source-of-truth boundaries, URL contract, capability boundary, BetterDocs runtime ownership, search/TOC/related/schema behaviors and non-destructive migration rules are now frozen sufficiently to begin V160-2 Module Shell. Remaining deep equivalence validation belongs to V160-3/V160-4 focused harnesses and bounded dry-run, not to destructive pre-work.
