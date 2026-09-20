# V170-3 — Admin Menu / Module Boot Integration Contract

## Goal

Provide one forward-compatible execution seam for future Andy modules without migrating existing v1.6 modules to a new runtime in the same release.

## Registration window

Andy Core opens:

`andy_core_register_modules`

during `plugins_loaded` before extension boot.

An external adapter can attach to this action and call:

`YBY_Module_Registry::register( $id, $metadata )`

This avoids plugin load-order coupling: the adapter does not need the Registry class to exist at its own file-load time.

## Extension runtime

`YBY_Module_Runtime` performs:

1. fire registration window once
2. read Registry V2 registered extensions
3. require module enabled
4. require dependencies satisfied
5. execute boot callback once
6. register admin-menu callback at declared priority
7. register settings callback at declared priority

Disabled modules do not boot and do not register menu/settings hooks.

Modules with an unknown/disabled dependency fail closed.

Foundation dependencies such as `core_runtime` are always considered available.

## Existing modules

The following existing modules keep their already validated v1.6 boot paths:

- Inquiry OS
- Email OS
- Project Studio
- Social Login
- Connector
- Andy Docs / docs_os

V170-3 does not migrate them through the extension runtime. This avoids a broad runtime behavior rewrite in the Foundation release.

## Menu contract

Registry metadata supports:

- `admin_menu`
- `admin_menu_priority`
- `capability`

The module enable/dependency gate is evaluated before the menu hook is registered.

Andy Core root shell remains independent and always available.

## Settings contract

Registry metadata supports:

- `settings` navigation target
- `settings_register` callback
- `settings_priority`

The extension runtime registers settings only for enabled, dependency-valid modules.

## Data contract

Runtime discovery / boot performs no option writes and no business-data writes.

Module disable does not uninstall or delete stored data.

## Scope boundary

V170-3 does not execute declared CSS/JS assets. Conditional assets are V170-5.

V170-3 does not implement Andy Template.

## Validation

`tests/v170-module-runtime-integration-harness.php` proves:

- future adapter discovery
- enabled module boot
- disabled module zero boot
- dependency fail-close
- menu/settings hook priority
- no double boot
- zero storage writes
- zero future asset hooks before V170-5
