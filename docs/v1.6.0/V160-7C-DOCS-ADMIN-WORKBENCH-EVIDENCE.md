# V160-7C — Docs OS Admin Workbench Evidence

Date: 2026-09-18

## Scope

Canonical v1.6.0 Docs OS admin workbench:

- Dashboard
- All Docs
- Editor
- Directory / Categories
- Settings

The final branch is rebased on main commit `2d8d610bacb1ebd848ae4c016b12f15dbef5a80e` (PR #45, GA4 Tracking Bindings), which itself includes PR #44 Glossy Global Inquiry Dock. Both PR #44 and PR #45 are inherited release baseline work and were not reimplemented in V160-7C.

## Profile 6 Local UAT

Local site: `https://localdev.beyourlover.com`

Final UI Automation + screenshot UAT passed for:

1. `admin.php?page=yby-docs-os`
2. `admin.php?page=yby-docs-all`
3. `admin.php?page=yby-docs-editor`
4. `admin.php?page=yby-docs-directory`
5. `admin.php?page=yby-docs-settings`

Local runtime was aligned to the current v1.6.0 worktree before final UAT:

- 129 tracked runtime files compared
- SHA256 mismatches: 0
- plugin header version: 1.6.0
- `YBY_CORE_VERSION`: 1.6.0

## Temporary document lifecycle UAT

A temporary Local-only Docs draft was created through the real logged-in Profile 6 Editor UI, edited a second time, previewed, and removed.

Validated:

- title + slug persistence
- Gutenberg block comments
- direct HTML/table content
- Shipping category assignment
- hierarchical `doc_tag` assignment
- direct Cloudflare R2 URL persistence
- Related Docs persistence
- Rank Math title / description / focus keyword persistence
- draft frontend preview
- second-save edit lifecycle

Validated R2 URL:

`https://img.beyourlover.com/2025/07/21054211/image.png`

## UAT defects found and fixed

### Hierarchical doc_tag persistence

BetterDocs registers `doc_tag` as hierarchical. Passing tag names directly to `wp_set_post_terms()` did not persist the terms.

Fix:

- resolve existing terms with `term_exists()`
- create missing terms with `wp_insert_term()`
- persist term IDs to `wp_set_post_terms()`

### Draft preview fallback

The canonical Docs Runtime originally rejected all non-published posts, so a valid WordPress draft preview could fall back to the first published Docs post.

Fix:

- draft rendering is allowed only when WordPress reports `is_preview()`
- the current user must also pass `current_user_can( 'edit_post', $doc_id )`
- ordinary canonical/public requests remain publish-only
- admin preview links use `get_preview_post_link()` for drafts

Post-fix Profile 6 preview assertions:

- temporary title visible: PASS
- temporary HTML heading visible: PASS
- R2 image visible: PASS
- 404: false

## Cleanup and data retention

Pre-UAT Docs state:

- Docs: 29
- postmeta: 481
- taxonomy relationships: 51
- fingerprint: `cb62962d180edc95f0c9a33754daec1913bd9946875891c2f7933aff8df797c6`

After deleting the temporary Docs post and its two test-only Tags:

- Docs: 29
- postmeta: 481
- taxonomy relationships: 51
- fingerprint: `cb62962d180edc95f0c9a33754daec1913bd9946875891c2f7933aff8df797c6`

Exact state restoration: PASS.

## Regression gates

PASS:

- inquiry-dock-dual-mode-harness
- inquiry-dock-source-contract-harness
- inquiry-modal-default-contract-harness
- inquiry-modal-default-runtime-harness
- docs-os-admin-dashboard-harness
- docs-os-admin-all-docs-harness
- docs-os-admin-workbench-harness
- docs-os-module-shell-harness
- docs-os-runtime-harness
- docs-os-canonical-harness
- docs-os-r2-asset-harness
- docs-os-reusable-contract-harness
- PHP lint
- git diff --check
- PR #44 ancestry

## Release state

- Production: untouched
- GitHub Release v1.6.0: not published
- existing `v1.6.0` tag still points to old `0f6e7eb6...` and must not be used for final release
- Final Tag Gate must use a merged main head that contains both PR #44 and V160-7C
