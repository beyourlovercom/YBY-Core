# Andy Core — Controlled L3 Menu Registry V1 (Dev-first contract)

**Scope:** one-time foundation for existing Andy Core WordPress L1/L2 navigation.
**Status:** Source Draft; not merged, not installed on Dev, not released.
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

## Integration with Currency PR #199 (not yet applied)

PR #199 is stacked on an independent Marketing branch. Do **not** merge its entire tree into Core V1 or hand-edit its branch in parallel.

Integration commit on the Currency side must:
1. Register the sample declaration above in the Commerce plugin.
2. Have `route_check` use the genuine Commerce currency tab controller and existing Dev-only `access()` gate.
3. Remove the transient `Andy_Commerce_Currency_V2_Dev` branch hardcoded in `admin/class-yby-andy-os-navigation.php` from PR #199 during eventual composite reconciliation.
4. Preserve `tab=currency-os&section=fx` and `currency_screen=ledger` routing inside Commerce. L3 link only points to `tab=currency-os`.
5. Build one integration candidate and verify that a *Commerce-only* declaration for a second test tab appears/disappears with **zero Core file changes**.

## Safety / checkpoints

`php tests/andy-core-l3-registry-v1-harness.php` checks permissions, real WP page registration, deterministic order, invalid URL/HTML, cross-page claims, missing routes, deactivation, and Commerce-only new-tab declarations. Existing `tests/*-harness.php` and Andy Commerce harnesses run in the Core Regression workflow.

**DoD still pending** until real Dev WordPress (PC/Desktop only) UAT, actual Core/Commerce installed-file SHA checks, negative user-role tests, rollback rehearsal, and Issue #196 closure notes. No main merge / Stable / WWW / real order or payment actions are authorized.
