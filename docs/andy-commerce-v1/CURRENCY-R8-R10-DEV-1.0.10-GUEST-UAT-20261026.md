# Andy Commerce Currency R8–R10 — Dev 1.0.10 installation and guest UAT (2026-10-26)

## Canonical release evidence
- GitHub sole source: Draft PR #213 (`beyourlovercom/YBY-Core`), exact head `4d38705635f7f575e1dd408430593acd34a0ad5f`.
- All required CI checks at exact head: **6 SUCCESS**, 1 conditional Currency OS preview SKIPPED, **0 FAILED**.
- Built artifact: `andy-commerce-dev-candidate.zip` from GitHub Actions run `40298356638`.
- Exact ZIP SHA256: `88e5d78a758d53a7e65aa70ee833f6839613b8787b7de30e50953a5da44b0532`.
- WordPress Dev plugin upload: `https://dev.beyourlover.com/wp-admin/update.php?action=upload-plugin&package=40018&overwrite=update-plugin&_wpnonce=(redacted)`, returned **Plugin updated successfully**. Do NOT circulate nonce in issue.
- Fresh Dev `/wp-admin/plugins.php` readback: **Andy Commerce active, Version 1.0.10**.
- Dev Currency manage page before this candidate: FX refresh from FRANKFURTER_REFERENCE successful at 2026-10-26 09:06 UTC; Runtime status **enabled-requested, FX valid**. Not a settlement FX quote. Full domain isolation: WWW not touched; production currency replacement not attempted.
- Marketing OFF preserved; main, Stable unchanged.

## Real browser results
1. `1.0.9` before fix: anonymous/incognito shopper POST selected EUR but reloaded to USD because no persisted Woo customer session.
2. `1.0.10` fix in R9 `set_customer_session_cookie(true)`; GitHub exact head CI PASS.
3. Fresh anonymous/incognito `https://dev.beyourlover.com/`: no WP admin bar; selected USD by default, visible USD products $99.00, $79.00, $119.00; Footer compact `[andy_currency_switcher]` form rendered.
4. Anonymous shopper selected EUR via actual Footer form, server POST and reload: selected EUR and native Woo product list €88.26, €70.43, €106.09; no login needed. Browser reload **retained EUR**; product prices still €88.26 and €70.43. This closes the prior guest session bug for home-page reload.
5. Same incognito shopper subsequently navigated to `/cart/`: recommended Woo product prices remained in EUR (e.g. €132.83, €85.58, €88.26) proving cross-page selection retained. Cart's independent template had **NO Footer currency switcher** (not injected automatically, per shortcode-only governance).
6. Separately, previously verified logged-in Dev Woo cart and actual `/wp-json/wc/store/v1/cart`: EUR product €43.68 + chosen CA flat shipping €8.92 = €52.60, API minor amounts 4368+892=5260. GBP 1-unit case £36.61 + shipping £7.47=£44.08, Store API minor amounts 3661+747=4408. GBP 2-unit case showed free-shipping option, which went away on quantity reduction; front-end message still denotes threshold in USD. These do **not** prove every destination's shipping matrix.
7. Anonymous Woo Store API cart readback with persistent EUR was **not separately captured** after 1.0.10: unrelated concurrent browser activity navigated/recycled the incognito window. Do not infer pass from logged-in Store API result.

## Final R8–R10 acceptance status
- R8 **PASS (scoped)**: Dev FX TTL, conflict gate, OFF/ON, WordPress install 1.0.10.
- R9 **PARTIAL PASS**: real anonymous Footer switch + cross-page refresh persistence PASS; **Cart/Footer shortcode placement remains OPEN**, and general anonymous Store API cart endpoint remains to be explicitly read back.
- R10 **PARTIAL PASS**: Woo product/cart prices and Store API for logged-in admin, one CA flat-shipping + free-shipping boundary checked; full country/tax/shipping and Marketing-by-USD invariant across all destinations not yet signed.
- Thus **DO NOT mark the entire R8–R10 as owner PASS**.
- R11+ production checkout, provider payment and production YayCurrency replacement are outside this phase and not authorized.
- Required next step: place the approved compact shortcode in the **Cart template's actual Footer**, rather than automatically injecting a second control globally; confirm native checkout/page template coverage and anonymous Store API session. Re-run UAT before owner signoff.
- No main merge, Stable or WWW deployment; do not change unrelated Cart branch templates unreviewed.

## No duplication / source policy
- Continue all Commerce integration in PR #213, avoid independently installing ZIPs from #207 or #225.
- Independent Cart Footer template is owned outside this Currency Runtime file area; coordinate its exact template placement without altering checkout/payment rules.
