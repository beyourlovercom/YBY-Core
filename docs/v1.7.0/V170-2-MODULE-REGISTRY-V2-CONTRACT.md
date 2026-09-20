# V170-2 — Module Registry Extension Contract V2

## Goal

Stabilize the existing `YBY_Module_Registry` so future Andy modules can register through a thin adapter without rewriting Core.

This is an incremental contract. It does not replace the existing runtime and does not execute new module callbacks yet.

## Compatibility guarantees

- Existing module IDs remain unchanged.
- Existing option key remains `yby_core_enabled_modules_v1`.
- No database migration.
- Existing legacy metadata keys `label`, `default`, `settings`, and `dependencies` remain available.
- Existing defaults remain unchanged:
  - Inquiry OS: ON
  - Email OS: ON
  - Project Studio: ON
  - Social Login: ON
  - Connector: ON
  - Andy Docs / `docs_os`: OFF

## Canonical normalized metadata

Every module returned by `YBY_Module_Registry::modules()` exposes:

- `id`
- `name`
- `label` (legacy alias of name)
- `description`
- `version`
- `schema_version`
- `default_enabled`
- `default` (legacy alias)
- `status`
- `capability`
- `bootstrap_class`
- `boot`
- `admin_menu`
- `settings`
- `settings_register`
- `assets`
- `dependencies`

## Adapter seam

Future code may call:

`YBY_Module_Registry::register( $id, $metadata )`

Rules:

- core module IDs cannot be overridden
- duplicate registrations are refused
- invalid/empty IDs are refused
- registered modules participate in the existing enabled-module option
- default OFF modules remain OFF until explicitly enabled
- planned modules cannot be persisted as enabled
- registering metadata does not itself boot runtime, add menus, enqueue assets, or write business data

## Dummy adapter proof

`tests/v170-module-registry-v2-harness.php` registers `future_module_probe` with:

- version/schema metadata
- capability
- bootstrap class
- boot callback
- admin-menu callback
- settings route + registration callback
- admin/frontend asset declarations
- dependency declaration

The harness proves the module can be registered and toggled using the existing option storage without any migration.

## Scope boundary

V170-2 does not implement Andy Template.

Execution of boot/menu/settings callbacks is intentionally deferred to V170-3. Conditional asset execution is deferred to V170-5.
