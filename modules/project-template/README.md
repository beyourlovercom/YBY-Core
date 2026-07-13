# Project Template Engine

## Purpose

Provide the orchestration layer that describes how one Project is assembled from runtime modules without taking over frontend rendering.

## Runtime Ownership

- PHP resolves the template object
- frontend exposes `window.YBYTemplate`
- theme and Bricks may continue rendering the existing page HTML

## Template Model

Each template defines:

- template identity
- page map
- content map
- tracking map
- asset map
- integration map

## Runtime Helpers

- `window.YBYTemplate.getTemplate()`
- `window.YBYTemplate.getPageMap()`
- `window.YBYTemplate.getContentMap()`
- `window.YBYTemplate.getTrackingMap()`
- `window.YBYTemplate.getAssetMap()`
- `window.YBYTemplate.getIntegrationMap()`

## Not In Scope

- dynamic rendering
- automatic page generation
- CRM implementation
- ERP implementation
- AI implementation
