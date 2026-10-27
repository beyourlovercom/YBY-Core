# Andy Core Notification UI V1 — R0–R2 (2026-10-27)

Source: \`beyourlovercom/YBY-Core\`, Issue #237. Feature branch: \`feature/andy-core-notification-ui-r0-r2-20261027\`.

## R0 — authoritative baseline audit

- Core plugin main has \`YBY_Module_Registry\` V2, \`YBY_Module_Runtime\`, centralized setting view \`admin/views/modules-page.php\`, with checkbox values persisted in \`yby_core_enabled_modules_v1\`.
- Admin entry is **Andy Core → 系统 → 模块** (\`admin.php?page=yby-core&tab=modules\`); it already uses the capability, nonce, and form processing needed. Do not add menus or a parallel \`notification_enabled\` option.
- Existing \`YBY_Notification_Manager\` dispatches lead/email notifications, not browser UI. Do not alter it.
- No shared Core-owned front-end notification island existed in \`main\`.
- The isolated \`dev.beyourlover.com/notification-lab/\` prototype already demonstrates Compact Edition and Latest Wins; it is *not* a WooCommerce integration or evidence of existing Core runtime.
- Cart/Checkout, order-pay, theme-specific and Blocks notice interception is explicitly deferred to **R3**. R0 did not classify an unverified native event as safe to intercept.

## R1 — module ownership, OFF by default

- \`inc/class-yby-notification-ui-module.php\` registers \`notification_ui\` through V2 Registry, only as a named feature in the existing consolidated module table.
- Registered with \`default_enabled => false\` and \`status => ready\`, because R2 foundation is functional even though Woo adapters are not yet included.
- Existing storage \`yby_core_enabled_modules_v1\` and existing nonce/capability/save paths remain authoritative. New installs and sites upgrading from versions with no \`notification_ui\` entry are OFF.
- OFF: no \`wp_enqueue_scripts\` hook from this module; no new front-end JS/CSS, Woo filters, form changes, checkout redirects, or native notice suppression.
- ON: loads only its JS plus a localized URL to its own scoped CSS; Woo native messages remain unchanged until R3 is separately implemented and accepted.
- Switching ON/OFF requires a fresh page load to activate the new server-side hooks.

## R2 — theme-independent compact island

- \`public/assets/js/andy-core-notification-ui.js\` owns the state machine. Event interfaces:
  - \`window.AndyCoreNotificationUI.notify({type,title,message?,key?,critical?})\`
  - \`window.AndyCore.notify(...)\` alias only if the target namespace is free.
  - \`window.dispatchEvent(new CustomEvent('andy-core:notify',{detail:{type,title,key}}))\`
  - The returned per-message handle \`{id,complete(next?),dismiss()}\` cannot displace or dismiss a newer message.
- Types: \`success\`, \`info\`, \`warning\`, \`error\`, \`blocked\`, \`running\`.
- Latest Wins replaces any prior message immediately (including compact or running); same-key 1200ms dedupe; 2000ms hold / 230ms exit for ordinary messages; critical auto-compact after 3000ms; running does not claim an automatic completion.
- Shadow DOM with Core-owned external stylesheet keeps this independent of theme CSS. Text is inserted via \`textContent\` (never HTML). No progress bar, bottom caption or decorative timing UI.
- Responsive 390px Chrome viewport verified: ordinary success title only; approx. 35px high, 163px wide for sampled “Saved on mobile”.
- Native Woo error/fallback guaranteed by *non-interception* at this stage. R3 must prove safe handoff before hiding any duplicate success. CSS failure currently leaves the island unavailable; adapter integration must not treat a returned handle as successful business proof.

## R0–R2 acceptance evidence

- PHP harness: \`tests/notification-ui-v1-r0-r2-harness.php\`: Registry default OFF, no hooks/assets OFF, central ON, ON→OFF preserving other modules, CSS/JS path + timings, core bootstrap.
- JS harness: \`tests/notification-ui-v1-r2-harness.js\`: state machine, auto dismiss, compact, Latest Wins, stale async protection, dedupe, one shadow host, no native Woo hiding.
- Chrome headless independent browser fixture: \`tests/fixtures/notification-ui-v1-browser.html\`. Verified 24 synthetic messages, stable shadow CSS in face of hostile theme style, compact after critical, immediate preemption, stale operation complete guard, dedupe, mobile viewport and automatic dismissal, no JS exceptions.
- Full existing PHP and JS harness regressions must pass at the exact feature HEAD before CI review; no production install, publication, tag, merge or website-side Core replacement is authorized by this branch.

## R3 separate gate

Woo classic, Store API / Blocks, Cart, Checkout, Order Pay, duplicate success and critical inline error behavior, actual native fallback after toggling OFF, and real Dev multi-theme verification. Do not silently classify R2 synthetic UI tests as R3 acceptance.

## Rollback

Default OFF; on a test installation using the feature source, disable \`notification_ui\` from **Andy Core → 系统 → 模块**, save, reload; since R2 has no Woo interception hooks, original native notices remain untouched. If Core plugin itself fails, restore the previously verified Core plugin package; test the normal plugin rollback pathway separately before any production release.
