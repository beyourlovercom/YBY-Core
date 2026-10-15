# Andy Core Admin Architecture — Permanent Governance Canonical V1

**Owner decision (2026-10-15):** All future **Andy-owned** WordPress plugin versions, self-developed modules, and newly approved admin requirements **MUST** integrate into the canonical V2.2 native WordPress three-level navigation architecture. New functionality is not a reason to create another top-level menu, separate UI skin, or duplicate page system. This policy supersedes the rejected V1 flat launcher and V2 post-DOM-movement implementation.

**Status:** Owner rule approved in chat; documented here in a **Draft PR**, not yet merged or CI-enforced on `main`. Runtime V2.2 integration and CI enforcement still require implementation and Owner UAT. Do not represent this governance document as a production rollout.

## 1. Scope and invariants

Applies to **Andy Core, Andy Commerce**, and future BYL/YBY self-developed WordPress plugins and admin modules (including new feature versions). An independent plugin retains its own code, versioning, capabilities, DB/data ownership and updater; **only its Andy-owned admin UX integrates** via a documented adapter.

- **L1:** one Andy-owned top-level menu visible in WordPress left sidebar, labeled **Andy Core**; preserve the legacy stable slug `yby-os`.
- **L2:** native WordPress child menus **under Andy Core in the WP left sidebar**. Approved initial categories: Inquiry OS; Newsletter OS; Email OS; Content OS; Marketing OS; Commerce OS; System & Connectors. User clicks a child **once** to reach the real existing work page. No compulsory Overview/cards, no separate second-level menu column in the content area.
- **L3:** feature tabs/navigation **only in the right-hand admin workspace's inner-left rail**, next to the original business content. One source of navigation per page; no duplicate old top tabs, floating second hierarchy, or two visual skins.
- **Rendering:** new/changed pages use a **server-registered, predictable PHP layout shell + shared styles**. JavaScript may enhance behavior but must **not** create the primary layout by moving arbitrary `.wrap` elements after `DOMContentLoaded`, or cause first-paint old-then-new UI flash. If an adapter is not ready, fail open to an intact original page/old route rather than leave a blank panel.
- **Unified VI:** color/spacing/typography, active state, loading/notice style and responsive behavior inherit the common Andy admin design tokens. Avoid per-plugin ad hoc CSS overlays; mobile is a first-class acceptance target (iPhone 14 / approx 390px).
- **Real content only:** L3 opens actual existing controller/page/form with authorized capability, not an empty marketing card or synthetic placeholder. Maintain all pre-existing URL/query/POST/nonce/breadcrumb/editor behaviors and actual feature state. Planned/Dev-only features must be clearly separated from active ones.
- **Security/data:** do not combine roles, silently grant capabilities, migrate business DB data, turn on production email delivery/Worker, or change commerce/payment/shipping merely because UI navigation is unified.
- **Preserve native WP flows:** Gutenberg, posts/pages, custom post types, taxonomies, permalink handlers and native media/editors must remain functional with correct menu highlighting.

## 2. Plugin interface contract (for EVERY future plugin release)

Every new Andy-owned admin feature must declare in a versioned **Admin Navigation Manifest**, integrated through a single supported adapter:
1. Owning plugin and tested minimum/maximum core adapter versions, module feature flag and enable/disable lifecycle.
2. L2 category (one of the approved seven) plus its original WordPress capability. Propose a new L2 **only with explicit Owner approval and menu-design UAT**, never silently.
3. L3 title, exact canonical admin route (and old aliases/redirect policy), permission, original view/POST handler, default destination and active-group highlighting.
4. Whether the page is active, Dev-only, read-only, disabled or planned; never display a fake active feature.
5. Unique shared style/component registration, priority/hook timing and screen-specific page wrapper/adapter; ability to fall back to the original plugin's own admin UI if Andy Core is absent.
6. Safe uninstall/upgrade/rollback contract: disabling a module cannot delete existing pages/data or strand old links. Preserve WordPress native editor routes.
7. Desktop + mobile screenshots, feature-level role tests, and latest baseline hashes for changes touching installed Dev runtime.

If a feature is purely frontend/no WordPress admin page, **it does not need a fabricated menu item**. If a third-party plugin is not owned by Andy (WooCommerce, Rank Math, AffiliateWP, WordPress native menus), keep its own menu and upgrade lifecycle; offer deep links from an Andy page only if appropriate and authorized. Do not commandeer third-party menu registration.

## 3. Permanently protected upgrade gates

### Design gate (new requirements and plugin upgrades)
- PR links this contract, lists **all new, changed, deleted, and renamed admin paths**, shows their L2/L3 mapping and expected default page, and includes a before/after page inventory.
- Each prior page must be marked preserved/migrated/deprecated with supported deep-link fallback; zero silently orphaned features.
- No new top-level Andy menus, duplicate third-level navigation, or unapproved L2 categories.

### Build / CI gate (implementation requirement, not yet active on main)
**Implement mandatory automated CI checks and branch protection before calling V2.2 production-ready**:
- Navigation manifest and registry conformance (exact one Andy L1, allowed L2, L3 route-owner/capability/feature-flag uniqueness).
- Real WordPress admin menu/route tests across plugin permutations (Core on/off; Commerce on/off; feature modules off; legacy links) and negative capability tests for admins, Inquiry-only, Newsletter-only, Email-only, Content-only, Woo manager.
- DOM integration tests for multiple `.wrap` containers, Inquiry list/detail, Commerce M3 draft/list, Newsletter/Email tabs, WP editors/CPT/taxonomies; no old/new UI double render or late DOM rearrangement.
- JS disabled/fallback safe handling; no unsupported direct DOM shifting or CSS-only concealment replacing contract-driven adapters.
- CSS and visual regression at desktop and iPhone 14/M390 including first paint, active L2/L3 highlights and notice dimensions.
- All original AJAX/actions, POST/nonces, forms and admin redirects preserved; no new real data writes as a side effect of rendering.

**Any failure blocks merge or deployment. CI PASS on a PR that lacks the required V2.2 checks is NOT a substitute for these gates.** Formal main branch protection/check requirement must be configured and verified separately; this document alone does not enforce a technical block.

### Release/Owner UAT gate
- GitHub main is the only code truth source; AICC/Notion are advice or progress mirrors, not alternative requirements authorities.
- Only allowlisted changed Dev runtime files; reconcile installed Dev overlays and exact hashes, preserve Andy Commerce/Three OS newer runtime, independent backup and tested rollback.
- Staged Dev deployment and real authenticated desktop/M14 Owner UAT come **before** Production. Owner acceptance requires actual feature/role/visual PASS, not merely PHP lint/CI and file hashes.
- A new plugin version may ship **only when it conforms**; nonconforming new admin functionality is blocked or protected behind a disabled feature flag until adapter and UAT pass.
- Never merge Draft PRs or deploy WWW without separate explicit authorization.

## 4. Exception process

An exception is possible **only when technically justified** (for example, third-party native admin screens or Gutenberg editor context), with a documented reason, original route/permission preservation, visual impact, rollback plan, and **explicit Owner signoff**. Do not force all WordPress ecosystem plugins into the Andy menu.

## 5. Required Owner UAT summary for each future admin-facing feature

| Required evidence | Expected |
|---|---|
| Native WP L1/L2 ownership | One Andy Core root; only approved role-visible groups |
| L3 completeness | New/changed functions appear directly in appropriate right-side feature navigation |
| Visual first paint | No old→new jump, duplicates, blank panel or CSS timing jitter |
| Original screen parity | Existing forms, redirects, logs, data and UI remain usable |
| Security | Role- and plugin-isolation checks; unauthorized direct links denied |
| Mobile | iPhone 14/M390 full screenshot PASS |
| Reversibility | Exact Dev rollback pointer and SHA verification |
| Owner | Explicit Owner UAT PASS; separate main / WWW gates |

## 6. Implementation status

The Owner **approves this as the permanent target contract**. Current Dev V2 has a known duplicate-old/new UI and DOM move defect; Owner visual UAT remains **FAIL/REWORK**. Draft PRs #105/#106/#107 remain unmerged. V2.2 remediation must implement this contract (starting with PHP shell and per-controller adapters), add mandatory CI enforcement, and then obtain real browser Owner UAT. This document is **not proof** that all existing versions already conform.

**Related:** [canonical route matrix](./ANDY-CORE-NATIVE-L123-NAVIGATION-V2-CANONICAL-20261014.md), [Issue #102](https://github.com/beyourlovercom/YBY-Core/issues/102), [Draft PR #106](https://github.com/beyourlovercom/YBY-Core/pull/106).
