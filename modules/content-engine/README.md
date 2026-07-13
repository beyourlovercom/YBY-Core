# Content Engine

## Purpose

Provide a content runtime architecture inside YBY Core without taking over frontend rendering.

## Runtime Ownership

- PHP resolves content structure and field values
- frontend exposes `window.YBYContent`
- theme and Bricks may continue rendering existing HTML

## Content Hierarchy

Project

-> Content

-> Sections

-> Blocks

-> Fields

## Current Scope

- section runtime model
- field runtime model
- content fallback priority
- frontend content helper methods

## Not In Scope

- dynamic section rendering
- template engine
- CRM
- ERP
- AI

## Frontend Helpers

- `window.YBYContent.getSection(id)`
- `window.YBYContent.getField(sectionId, fieldId)`
- `window.YBYContent.isEnabled(sectionId)`
- `window.YBYContent.getOrderedSections()`
