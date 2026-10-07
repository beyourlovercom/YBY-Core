# Andy Core v1.9.0 Upgrade Guide

## Supported path

Primary upgrade path:

Andy Core 1.8.1 -> Andy Core 1.9.0

The plugin path remains wp-content/plugins/yby-core/.

No database migration is required.

## Dev-first validation

For the BYL Website V3 program, perform integration and Owner UAT on dev.beyourlover.com.

The Dev code must map to an exact GitHub HEAD / PR / release candidate.

localdev.beyourlover.com may be used for isolated experiments or bug reproduction but is not the required long-term integration authority.

## Upgrade checks

1. Backup the current yby-core plugin directory.
2. Install/replace the Core code with the exact 1.9.0 candidate.
3. Confirm the plugin header and YBY_CORE_VERSION both report 1.9.0.
4. Confirm runtime DB remains 1.5.0.
5. Confirm Core-only admin and public runtime remain healthy.
6. Confirm Addons page loads without requiring any Addon.
7. If Andy Commerce is installed, confirm it registers as an Addon and dependency status is Ready.
8. Run the focused Core regression and release metadata gates.

## Production boundary

Do not upgrade the current www.beyourlover.com merely because Dev passes.

Production remains unchanged until the separate Final Cutover Gate is approved.
