# Andy Core v1.9.0 Release Notes

## Scope

Andy Core v1.9.0 is the Addon Foundation release.

It adds the generic contract required by approved first-party Addons while keeping product-specific business runtime outside Core.

### Included

- YBY_Addon_Registry
- andy_core_register_addons
- installed/inactive Addon discovery
- Core-version compatibility reporting
- Addon dependency readiness reporting
- read-only Addons administration
- Addon status in System Status

### Explicitly not bundled

- Andy Commerce runtime
- Woo Order Export
- Checkout / Shipping / Payment / Customer commerce behavior
- any new B2C business logic

Andy Commerce is maintained as a separate approved Addon package.

## Compatibility

- Plugin directory remains yby-core/.
- Existing YBY_* / yby_* contracts remain compatible.
- Runtime database version remains 1.5.0.
- Signed-updater compatibility database version remains 1.4.0.
- No database migration is required.

## Environment policy

The new BYL Website is developed and integrated primarily on dev.beyourlover.com.
localdev.beyourlover.com is optional for isolated/high-risk work.
The existing www.beyourlover.com production site is not changed by this release closure.

Production deployment and final Website V3 cutover require separate explicit approval.
