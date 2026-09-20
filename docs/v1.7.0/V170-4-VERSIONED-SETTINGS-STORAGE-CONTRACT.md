# V170-4 — Versioned Settings / Storage Contract

## Goal

Provide one stable storage seam for future Andy Core modules without forcing existing modules to migrate their business settings or option keys.

The Foundation release standardizes storage behavior, not business schemas.

## Registry metadata

A module may declare:

`storage => [ option_key, schema_version ]`

If a module declares storage but omits `option_key`, Registry V2 derives:

`yby_{module_id}_settings_v{schema_version}`

The derived key is deterministic and versioned.

## Canonical Store

`YBY_Module_Settings_Store` exposes:

- `contract( $module_id )`
- `option_key( $module_id )`
- `schema_version( $module_id )`
- `exists( $module_id )`
- `get( $module_id, $defaults, $sanitize_callback = null )`
- `save( $module_id, $raw, $sanitize_callback )`

No destructive delete API exists in Foundation.

## Read contract

`get()`:

1. resolves the module storage contract
2. reads the declared option key
3. treats non-array stored values as empty
4. merges stored values over supplied defaults
5. optionally applies the module-owned sanitizer
6. performs zero writes

A read must never materialize defaults into the database.

## Write contract

`save()` requires a callable sanitizer.

If no valid sanitizer is supplied, the write fails closed.

After sanitization:

- new storage uses `add_option(..., autoload=false)`
- existing storage uses `update_option(..., autoload=false)`
- only array payloads are accepted

The Store intentionally does not define business-field sanitization rules.

## Data retention

Disabling a module does not delete its settings.

The Store exposes no delete method.

Uninstall/data-deletion behavior remains a separate explicit product and governance decision.

## Existing module compatibility

### Andy Docs

Andy Docs keeps the existing option key:

`yby_docs_os_settings_v1`

Registry storage metadata:

- option key: `yby_docs_os_settings_v1`
- schema version: `1`

`YBY_Docs_OS_Admin::get_settings()` now reads through the Store while preserving the exact legacy option and defaults contract.

Existing Docs settings are not copied, renamed, rewritten, or migrated.

Docs write paths retain their already-reviewed field sanitization behavior.

### Social Login / Email OS

V170-4 does not migrate their existing storage implementations.

Their strong module-specific sanitizers remain authoritative.

They may opt into the common Store in a future bounded migration if there is a real product need.

## Future modules

Future modules such as Andy Template may declare only:

`storage => [ schema_version => 1 ]`

and automatically receive:

`yby_{module_id}_settings_v1`

The module still owns its defaults and sanitizer.

## Existing-site module adoption

The one-time `landing_pages` adoption introduced during V170-3.6 uses the same non-destructive storage principles:

- append only the newly introduced default module
- preserve all previous explicit enable/disable choices
- persist an adoption marker
- never force-reenable after the owner disables it

## Scope boundary

V170-4 does not:

- create custom database tables
- migrate existing Docs/Social/Email options
- delete legacy options
- automatically rewrite stored values
- provide cross-module business schemas
- implement Template OS settings

## Validation

PASS requirements:

- Docs legacy option key unchanged
- Docs existing values read correctly through Store
- missing defaults merge in memory only
- read path performs zero writes
- new module option key is deterministic and versioned
- unsanitized writes fail closed
- new writes use autoload=false
- updates use autoload=false
- Store exposes no delete API
- existing module/runtime regressions remain green
