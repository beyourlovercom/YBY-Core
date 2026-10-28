# Andy Core Notification UI — R7 First Paint + Native Minimal Error (2026-10-28)

## Owner clarification and issue

During **real** R5 sandbox guest testing (not the synthetic visual buttons), sold-individually add-to-cart and invalid My Account login produced two visual presentations: a bulky WooCommerce native error appears first, then the fixed island shows the same failure and removes the first element causing unwanted layout shift/flicker. The original native error also had large red background, border, unwanted focus outline and word/icon overlap. The first-paint issue was discovered **after** v2.3.0 Stable publication.

**Final R7 visual ownership (supersedes the R5 all-errors-island policy):**
- Customer-visible success and ordinary information: Core-owned Dynamic Island, when enabled.
- Errors, stock issues, invalid coupon, authentication/checkout/payment failures, warnings: **native-only** (no island duplicate). Native notice still uses Core-owned minimalist micro-line skin.
- Contextual field validation stays beside the field, with native field navigation and actionable links.
- OFF restores native Woo success/error behavior, retaining minimalist Core skin.
- Missing JS, CSS, slow load or invalid transfer => native stays visible/fail-open. No Woo business events, login/order/payment modifications.

## Visual design (Owner screenshot review)

**Minimal Style A** chosen as Dev candidate:
- 0px outer border, transparent background, no outer box shadow, no full-width banner.
- Compact `width:fit-content`, single-line native error about 33.5px high; naturally wraps long errors.
- 13px text / 1.5 line height with icon in reserved 28px left padding; icon alone uses muted wine-red.
- Inline `View cart` link, no rectangular CTA shell. Links retain keyboard `:focus-visible` styling.
- Programmatic Woo `tabindex=-1` focus on notice container does not produce duplicated selected border.
- Retains `role=alert` and DOM focus semantics, does not hide native error.

## Implementation (source branch only, NOT v2.3.0 Stable)

`feature/andy-core-notification-first-paint-r7-20261028` branches off exact published v2.3.0 source main `9edcbfb6498a69e77886bb40b3db61cdf176910a`:
- `inc/class-yby-notification-ui-module.php`: early `wp_head` *success/info only* scoped temporary preflight CSS to prevent first-paint native success flash before the island loads. Inline JS timeout 2500ms restores native if deferred bridge never starts; no error/warning selector hidden.
- `public/assets/js/andy-core-notification-woo-classic.js`: only success/info selectors, no error/warning native suppression or island. Preflight notices still eligible despite CSS display:none when suppressed **by Core itself**; release gate after bridge pass; complex links remain native, trailing action label removed from island text. Unexpected errors release gate.
- `public/assets/css/andy-core-native-notice-skin.css`: native micro-line skin for success/info/error/warning/Woo Block notice banners; visual is no-bg/no-frame/no-shadow, preserving all native interactivity and role/focus.

## Isolated sandbox and real end-to-end results

**Isolated site only**: `https://dev.beyourlover.com/aicc-notification-r5-20261027/`, prefix `r5nu_261027_`. Shared Dev WordPress plugin and all Production sites not changed. The isolated site still identifies itself as Core v2.2.12 because the R7 candidate PHP/JS/CSS are staged as selected files; it is not a v2.3.0/2.3.1 installed package verification.

- Real sold-individually product second add-to-cart: **ONE** native `.woocommerce-error`, visible, `role=alert`, `View cart` link kept, `islandState=null`, `bridge.accepted=0`, 0 JS exceptions. Native height **33.5px** with transparent background/border 0.
- Programmatic focus on real native product error: `outline: none 0px`, `box-shadow:none`, border 0, link remains present.
- Real My Account invalid login using unregistered test username and isolated site credential-free guest: **ONE** native error, `islandState=null`, `bridge.accepted=0`, height 53px due to longer wrapping, transparent background, no border or shadow, 0 JS exceptions.
- Real successful add-to-cart in a fresh Chrome profile: native success hidden **from first observed frame** (0 visible original notice frames out of 40 sampled), success displayed directly in island, only one acceptance, 0 JS errors.
- PHP/JS notification regression harnesses PASS, no business network calls from notification adapter.
- Re-labeled sandbox-only `/notification-ui-uat/` to represent new policy, with separate success island, native error and field error buttons (synthetic demo).

## Gates

- R7 Dev visual UAT (Owner) pending.
- GitHub Draft PR/head exact CI required.
- No main merge without new exact-head Owner PASS.
- Cannot republish/re-sign already published v2.3.0 with different bytes. Future signed hotfix should be a **new version (proposed 2.3.1)** with separate release approval.
- No automatic site install, no Production changes, no device reboots or global Chrome session interruption.
