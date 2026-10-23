# Andy Core — Controlled L3 Menu Registry V1 (Dev-first contract)

**Scope:** one-time foundation for existing Andy Core WordPress L1/L2 navigation.
**Status (2026-10-23):** Draft GitHub PR #206, Dev-only installed and functionally verified, Owner desktop visual PASS. Not merged to main, not released as Stable, not deployed on WWW. Restricted-role Dev login UAT remains pending.
**API:** `andy_core_l3_external_items` filter, collected at native L3 rendering. No Core business tabs need to be hardcoded for future supported extensions.

## Contract for a business plugin

Business plugin registers its filter at plugin bootstrap (or at `admin_menu`).
When Core generates the current page's L3 rail, it calls:

```php
add_filter( 'andy_core_l3_external_items', static function ( $items, $page ) {
    if ( 'andy-commerce' !== $page ||
         ! class_exists( 'Andy_Commerce_Currency_V2_Dev' ) ||
         ! Andy_Commerce_Currency_V2_Dev::access() ) {
        return $items;
    }
    $items[] = array(
        'id'          => 'andy-commerce.currency-os',
        'parent'      => 'andy-commerce',
        'page'        => 'andy-commerce',
        'title'       => '多币种',
        'tab'         => 'currency-os',
        'capability'  => 'manage_woocommerce',
        'after'       => 'settings',
        'route_check' => static function ( $tab, $page ) {
            // MUST check the controller actually implements and exposes this tab.
            return 'andy-commerce' === $page && 'currency-os' === $tab &&
                class_exists( 'Andy_Commerce_Currency_V2_Dev' ) &&
                Andy_Commerce_Currency_V2_Dev::access();
        },
    );
    return $items;
}, 10, 2 );
```

Core only ever builds `admin_url('admin.php?page=andy-commerce&tab=currency-os')` from the validated page and tab. It never accepts an external URL. A plugin without the filter API remains independently usable; no direct calls to a new Core method are needed. Register the filter **only in the actual business plugin**, not in Core.

## Validation semantics

- Supported existing WordPress host pages in V1: `andy-commerce`, `andy-core-leads`, `andy-core-newsletter`, `andy-core-email`; V1 intentionally does not register new L1/L2 hosts.
- Core confirms `admin_menu` has run, original page's WP callback exists, host minimum capability passes, declared capability passes, parent/page/id namespace all equal the current host, and `route_check($tab,$page) === true`. The real controller must separately deny unauthorized direct links.
- Accept only `id,parent,page,title,tab,capability,after,route_check`. `id` must start with `<host-page>.`; `tab` and `after` are safe slugs; labels cannot contain markup/control characters. All query parameters except `page` and `tab` are disallowed; Core constructs the URL.
- Optional `after` names an **existing Core native tab** (e.g. `settings`); omitted appends after the last native link. Equal anchors retain declaration order. Unknown anchors are rejected, not silently rendered elsewhere.
- A native tab cannot be overridden; duplicate ID or route rejected. Invalid/throwing declarations are skipped; native menus remain unchanged. Repeated reads are deterministic; plugin deactivation removes its declarations.
- Roles restricted to Newsletter/Mail/Inquiry cannot use the Woo host. Page owners MUST retain their existing endpoint cap/nonces, module gates and valid-tab allowlist.
- This is a contract between **trusted installed WordPress PHP plugins**. A malicious PHP plugin has full WordPress execution privileges, so no filter can provide cryptographic plugin-origin isolation. V1 protects declarative mistakes/overreach; it does not sandbox untrusted PHP.

## Integration with Currency PR #199 (Dev-only applied; source merge pending)

PR #199 is stacked on an independent Marketing branch. PR #206 (based on Currency/Footer-only head `c1ec48c2f715d4e34c807c5d303e0f03bf86ec65`) already reconciles the Core and Commerce adapters for Dev. Keep future Commerce builds based on the latest PR #199 + PR #206 adapter; deploying a Currency-only ZIP without the adapter will erase the registered menu. Do **not** merge PR #199's business tree into the Core V1 foundation or hand-edit the active Currency branch in parallel.

Integration contract currently implemented in Draft PR #206:
1. Register the sample declaration above in the Commerce plugin.
2. Have `route_check` use the genuine Commerce currency tab controller and existing Dev-only `access()` gate.
3. Remove the transient `Andy_Commerce_Currency_V2_Dev` branch hardcoded in `admin/class-yby-andy-os-navigation.php` from PR #199 during eventual composite reconciliation.
4. Preserve `tab=currency-os&section=fx` and `currency_screen=ledger` routing inside Commerce. L3 link only points to `tab=currency-os`.
5. Build one integration candidate and verify that a *Commerce-only* declaration for a second test tab appears/disappears with **zero Core file changes**.

## Safety / checkpoints

`php tests/andy-core-l3-registry-v1-harness.php` checks permissions, real WP page registration, deterministic order, invalid URL/HTML, cross-page claims, missing routes, deactivation, and Commerce-only new-tab declarations. Existing `tests/*-harness.php` and Andy Commerce harnesses run in the Core Regression workflow.

**Current verified evidence:** Dev WordPress Core 2.2.11 + Commerce 1.0.8 active, exact installed source fingerprints: Core nav `8a34d409`, Core L3 registry `6bdf01fc`, Commerce admin `c4ae412c` (FNV-1a-32; do not confuse with SHA256). Real authenticated Dev HTTP 200 on Commerce overview, Currency, FX, legacy Ledger, and Marketing; exactly one Currency L3 item; six Currency tabs. Owner explicitly approved desktop visual UAT on 2026-10-23. Rollback plugin ZIP hashes and Dev candidate manifest were preserved on the China Mac (see Issue #196). No real order/payment/Cron/email tests were run.

**Remaining gates:** isolated genuine WordPress/MySQL restricted-role CI (subscriber/editor), then any required final Dev non-admin login/direct-route negative UAT using only authorized disposable test accounts; no customer-account reuse. The test accounts in GitHub Actions disposable WordPress are **not Dev accounts**. Only close the final security gate when evidence supports it. Before any merge, reconcile current exact heads and record a fresh review. Main merge, Stable release and WWW deployment each require independent Owner approval.
