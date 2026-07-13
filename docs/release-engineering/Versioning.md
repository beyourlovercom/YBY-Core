# Versioning

## Product version

The plugin header version and `YBY_CORE_VERSION` must move together.

## Release stage

Release stage labels such as `RC1` are release-engineering labels. They do not change the WordPress plugin version number unless a separate semantic version is intentionally approved.

For this line:

- Plugin version: `1.2.0`
- Release stage: `RC1`
- Release label: `YBY Core v1.2.0 RC1`

## Compatibility rule

The v1.x public browser runtime API remains frozen:

- `window.YBYCoreConfig`
- `window.YBYProject`
- `window.YBYContent`
- `window.YBYTemplate`
- `window.YBYLead`
- `window.YBYTracking`
- `window.YBYThankYou`
- `window.YBYPageProfile`

Additive capability is allowed. Breaking renames or removals are not.
