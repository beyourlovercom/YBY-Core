# Andy Core v1.7.0 — Article TOC V1 Local UAT Evidence

Date: 2026-09-20

Branch:

`feature/andy-core-v1.7.0-foundation`

Feature implementation commit:

`aa03ad362755e9f61611b54f62276563ae890fae`

Owner UAT: **PENDING**

Production: **UNTOUCHED**

## Scope

Article TOC V1 is integrated as a Registry V2 extension module:

- module id: `article_toc`
- display name: `文章目录`
- default enabled: false
- V1 post-type scope: `post`
- storage: `yby_article_toc_settings_v1`
- storage schema: `1`
- runtime ownership: Andy Core
- theme ownership: CSS variable / brand-token overrides only
- no database table
- no business-data migration

## Foundation reuse

The module reuses the already completed v1.7 Foundation contracts:

- `andy_core_register_modules`
- Registry V2 metadata
- enabled-only extension boot
- dependency fail-close
- `YBY_Module_Settings_Store`
- conditional asset runtime
- request-level asset condition

No second module framework was introduced.

## Settings UI

Existing UI path:

`Andy Core → Settings → 模块`

Registry row:

`文章目录 | 自动为博客文章生成 Inline Summary、桌面悬浮目录、H2 锚点与 Scroll Spy | Feature | ON/OFF | 设置`

Settings tab:

`Andy Core → Settings → 文章目录`

V1 fields:

- directory title / default `Summary`
- minimum H2 count / default `2`
- desktop floating breakpoint / default `1200px`
- post types / V1 allowlist only `post`

The module enabled flag remains owned only by Registry V2. No duplicate enabled setting is stored in the Article TOC settings option.

## OFF baseline

Before UAT the Local enabled-module list did not contain `article_toc`.

A temporary published WordPress Post was created:

- post ID: `39919`
- slug: `uat-article-toc-v1-20260920`

With Article TOC OFF:

- HTTP status: 200
- Article TOC CSS reference: 0
- Article TOC JS reference: 0
- Andy TOC DOM marker: 0
- article body/H2 content: present

Result:

**PASS — OFF = zero TOC boot/assets/DOM.**

## ON asset gate

Local-only UAT enabled `article_toc` and saved:

- post_types: `post`
- title: `Summary`
- min H2: `2`
- breakpoint: `1200`

Eligible single-post HTML then contained:

- `yby-article-toc.css`: 1
- `yby-article-toc.js`: 1
- `AndyArticleTOCConfig`: 1

PHP harness also validated that:

- admin request: no frontend assets
- feed request: no frontend assets
- page: no frontend assets
- `yby_landing_page`: no frontend assets
- `docs`: no frontend assets
- eligible single `post`: assets allowed

## Existing BeYourLover legacy TOC detection

The real BeYourLover Local child theme currently still owns a historical TOC implementation.

Observed legacy DOM/runtime:

- `#toc-spy.toc.stiky`
- `.articre_toc`
- `.articre_toc_ul`
- runtime anchors `#test_0`, `#test_1`, ...
- theme script handle: `custom-single-script`
- theme style handle: `custom-single-style`

When the legacy TOC was present on the temporary post:

- Andy Core Article TOC CSS/JS were correctly gated onto the eligible Post
- Andy Core detected the legacy marker
- Andy Core generated **0** additional TOC DOM
- no duplicate TOC was created

Result:

**PASS — BeYourLover compatibility detection / duplicate prevention.**

This intentionally leaves the old theme runtime in control until a later site-by-site ownership migration.

## Clean Article TOC runtime fixture

To validate Andy Core ownership independently, a Local-only temporary MU UAT shim was created for post ID `39919` only.

The shim:

- selected a clean single-post template for only the temporary UAT post
- dequeued/deregistered `custom-single-script`
- dequeued/deregistered `custom-single-style`
- did not modify the child-theme source files
- did not affect any other post

The shim and clean template were deleted after UAT.

## Runtime DOM contract

Temporary content contained:

- intro paragraph
- H2 `First Section`
- H2 `Second Section` with existing ID `existing-stable-anchor`
- H2 `Third Section`
- a `.related-posts` section containing H2 `Related Posts`

Andy Core generated:

- inline TOC count: 1
- floating TOC count: 1
- inline TOC before first H2: PASS
- generated first anchor: `andy-toc-first-section`
- existing second anchor preserved: `existing-stable-anchor`
- generated third anchor: `andy-toc-third-section`
- Related Posts TOC links: 0
- legacy TOC markers in clean fixture: 0

## Responsive browser UAT

Validation used a separate temporary headless Chrome profile through Chrome DevTools Protocol. The logged-in owner Chrome/Profile 6 was not restarted or modified.

### 1440px

Initial:

- inline TOC: 1
- floating TOC: 1
- floating visible before entering article range: no
- horizontal overflow: 0
- active section: First Section

After scrolling to Second Section:

- floating class visible: yes
- computed display: `block`
- active section in both TOCs: Second Section

After clicking Third Section in Inline TOC:

- active section in both TOCs: Third Section
- target heading top: ~120px
- smooth/offset navigation contract: PASS

### 768px

- inline TOC: 1
- floating TOC DOM: 1
- floating visible: no
- computed floating display: `none`
- horizontal overflow: 0

### 390px

- inline TOC: 1
- floating TOC DOM: 1
- floating visible: no
- computed floating display: `none`
- horizontal overflow: 0

Result:

**PASS — Desktop floating + Inline Summary + responsive visibility + overflow contract.**

## Scroll Spy / accessibility

Validated runtime contract:

- active section updates on scroll
- clicking an item updates to the target section
- both visual TOCs represent the same single active section
- active links use `aria-current="location"`
- TOC uses semantic `nav` / `aside`
- links use normal `href="#anchor"`
- scroll work is requestAnimationFrame-throttled
- no per-scroll full DOM discovery

## Focused tests

PASS:

- `article-toc-module-harness.php`
- `article-toc-runtime-harness.js`
- Registry V2 regression
- Module Runtime regression
- Settings Store regression
- Module Registry regression
- Module Boot Gate regression
- PHP lint for changed PHP files
- `git diff --check`

## Full regression

A detached clean worktree at the Article TOC commit was used so the separate, unfinished v1.7 Release Identity WIP could not affect release metadata tests.

Results:

- Git-tracked PHP lint: **165 PASS**
- PHP harnesses: **58 PASS total**
  - 57 ran in the detached clean worktree
  - the pre-existing V170 Admin IA source-string harness is CRLF-sensitive on a Windows detached checkout and was run separately in the main worktree: PASS
- JS harnesses: **7 PASS**
- Article TOC PHP harness: PASS
- Article TOC JS harness: PASS
- release metadata/updater regression at the clean commit: PASS
- `git diff --check`: PASS

No Foundation business/runtime regression was detected.

## Cleanup

After Local UAT:

- temporary Post 39919: permanently deleted
- Article TOC module state: restored to OFF
- temporary `yby_article_toc_settings_v1`: removed because it did not exist before UAT
- original enabled-module list restored
- temporary MU UAT shim: removed
- temporary clean template: removed
- temporary headless Chrome processes/profile: removed
- Production: untouched
- theme source: untouched

Final Local enabled modules restored to:

- inquiry_os
- email_os
- project_studio
- docs_os
- landing_pages

## Result

**Article TOC V1 engineering + Local functional UAT: PASS**

**Owner UAT: PENDING**

The Owner must still visually inspect:

- Andy Core → Settings → Modules row for `文章目录`
- Article TOC settings UI
- Desktop floating position/style on a real target theme
- Inline Summary visual treatment
- Scroll Spy feel
- Mobile appearance
- OFF → both TOCs disappear
- ON → expected TOC behavior returns

No Owner PASS is claimed by this evidence.
