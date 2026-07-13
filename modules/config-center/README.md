# Config Center Module

## Purpose

Provide one central options layer for YBY Core settings.

## Current MVP Status

- default options exist
- sanitization exists
- getter helpers exist
- admin settings page uses the options layer
- frontend runtime config output exists via `window.YBYCoreConfig`
- page profile values can override selected runtime values per page

## Future Roadmap

- stronger settings UX
- environment-aware defaults
- module-specific validation helpers
