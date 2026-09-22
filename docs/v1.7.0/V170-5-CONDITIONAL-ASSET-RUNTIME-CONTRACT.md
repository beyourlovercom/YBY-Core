# V170-5 — Conditional Asset Runtime Contract

## Goal

Prevent future Andy modules from globally loading CSS/JS simply because the module is enabled.

Asset execution must pass both module-level and page/runtime-level gates.

## Registry metadata

Registry V2 normalizes two channels:

- admin
- frontend

Each channel exposes:

- enqueue callback
- condition callback
- priority
- optional declared handles

Example shape:

`assets.admin = [ enqueue, condition, priority, handles ]`

`assets.frontend = [ enqueue, condition, priority, handles ]`

Declared handles are metadata only. The module-owned enqueue callback remains responsible for registering/enqueuing actual WordPress assets and URLs.

## Runtime gate order

For a registered extension module:

1. module exists
2. module is enabled
3. module dependencies are satisfied
4. extension boots exactly once
5. asset channel has a callable enqueue callback
6. asset channel has a callable condition callback
7. WordPress reaches the relevant enqueue hook
8. condition callback returns true
9. enqueue callback runs

Failure at any earlier step results in zero asset enqueue.

## Fail-closed condition rule

A callable enqueue callback without a callable condition callback does **not** register an asset hook.

This prevents an omitted condition from accidentally turning a module asset into a site-wide/admin-wide asset.

If a module intentionally needs global assets, it must explicitly provide a condition callback that returns true.

## Admin channel

Hook:

`admin_enqueue_scripts`

The condition receives:

- WordPress hook suffix
- normalized module metadata

The enqueue callback receives the same values.

This allows a module to restrict assets to its own admin screens.

## Frontend channel

Hook:

`wp_enqueue_scripts`

The condition receives normalized module metadata.

The module may inspect WordPress request state inside its condition callback.

The enqueue callback is executed only if that condition returns true.

## Existing built-in modules

V170-5 does not migrate the already validated built-in Inquiry / Email / Project / Social / Connector / Docs asset paths.

They retain their existing v1.6/v1.7 hooks.

The conditional asset seam is the forward-compatible contract for modules registered through Registry V2.

## Idempotence

Repeated module discovery does not:

- double boot the extension
- duplicate admin asset hooks
- duplicate frontend asset hooks

## No storage side effects

Asset registration and asset dispatch perform zero settings/business-data writes.

## Validation

The extension-runtime integration harness proves:

- enabled extension registers conditional asset hooks
- dependency-blocked extension registers no asset hooks
- disabled extension registers no asset hooks
- enabled extension missing condition fails closed
- admin false condition does not enqueue
- admin matching condition enqueues exactly once
- frontend false condition does not enqueue
- frontend true condition enqueues exactly once
- declared priorities are respected
- repeated discovery does not duplicate hooks
- module runtime performs zero option/business writes
