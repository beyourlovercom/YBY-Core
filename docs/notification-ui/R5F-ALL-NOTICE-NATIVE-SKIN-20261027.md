# Andy Core Notification UI V1 — R5-F All-Notice Island + Native Skin (2026-10-27)

Parent issue #237 / #239. Stacked on prior R3–R5 Draft PR #240. Isolated feature branch: `feature/andy-core-notification-all-notices-r5f-20261027`.

## Owner requirement

1. When `notification_ui` is enabled, WooCommerce native success, informational, warning and error notices should be displayed by the Core-owned compact Dynamic Island rather than visually broken theme banners.
2. Native notices are **also** beautified by a compact, theme-independent Core CSS skin. The skin works while the island module is OFF and whenever a contextual notice must remain inline; the native notice markup, business logic and screen-reader information remain intact.
3. No lost payment/stock/checkout/account error. CSS/JS unavailable, action cannot be transferred or unexpected browser error => keep / restore native.
4. Only the existing `Andy Core → 系统 → 模块` switch. No new admin page or theme/Commerce version dependency.

## Implementation

- `public/assets/js/andy-core-notification-woo-classic.js`: all-type Woo frontend DOM adapter, including product, account, cart, checkout, order-pay and supported Woo block notice banners. Do not globally intercept business events or hide them with CSS; each node is suppressed only following an accepted, styled island display.
- `public/assets/js/andy-core-notification-ui.js`: optional safe same-origin action link rendering (e.g. View cart); critical messages remain accessible/persistent rather than disappearing after 2 seconds.
- `public/assets/css/andy-core-native-notice-skin.css`: styles Woo classic success/info/error, coupon errors, block-style notices, both desktop and mobile; 1px soft border, shallow background, consistent 18px pseudo-icon and 40px padding; avoids old Woo/theme icon overlap. Native hidden states are respected (no unconditional display:block).
- `inc/class-yby-notification-ui-module.php`: enqueue CSS skin on Woo frontend with Core independently of island toggle; ON additionally loads island JS and adapter sitewide. OFF omits all island scripts and leaves native notices functional but compact.
- Contextual field-validation messages remain inline with compact native skin, while island also summarizes the error. Nontransferable buttons/forms, multi-action or external URL notices remain native to prevent functionality loss. Transferred same-origin links are rendered inside island.
- Critical previous errors are restored selectively if a new critical event supersedes them, avoiding silently losing an actionable business failure; already handled ordinary success banners are not restored. The bridge fails open on runtime failures and page navigation.

## Test evidence

- PHP R0/R1 module registry and R3 route harness PASS; JS R2 state engine and all-type bridge harness PASS.
- WordPress isolated R5 Dev test site: `https://dev.beyourlover.com/aicc-notification-r5-20261027/`. No shared Dev files modified.
- Dedicated Owner preview: `https://dev.beyourlover.com/aicc-notification-r5-20261027/notification-ui-uat/`. Four actions: success island; invalid coupon error island; Latest Wins; contextual native compact skin. These are **synthetic DOM preview buttons**, not coupon/order transactions.
- Headless Chrome on BYL Theme V3 and Twenty Twenty-Five: ON island success/error, critical persists, Latest Wins, contextual native skin 44px, icon 13px left/18px width and 40px padding (non-overlap), zero JS exceptions. OFF: island scripts absent; native success/error skin stays active and compact (~44px), no JS exceptions.
- Earlier R5 proof also established real Woo Classic AJAX coupon success/error semantics and an isolated WordPress admin nonce module save; these are distinct from the synthetic Owner preview.
- GitHub Linux CI, exact head and new Owner visual acceptance remain separate gates.

## Boundaries

- No main merge, Stable, WWW deployment or non-isolated Dev plugin overwrite.
- Not claiming every possible third-party arbitrary alert DOM schema; the automatic adapter covers documented Woo frontend families. Structured field validation and payment gateways remain authoritative. Special plugin messages will need named adapters when proven.
- Module OFF restores native *behavior* and visibility with **compact skin**; disabling Andy Core itself restores the theme's own styles (Core cannot style after deactivation).
