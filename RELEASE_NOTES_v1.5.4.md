# Andy Core v1.5.4 Release Notes

Release type: Stable feature release
Plugin version: 1.5.4
Database version: 1.4.0

This bounded release adds a private GitHub Releases updater. WordPress native update discovery and Update Now use an authenticated allowlisted GitHub API path. Packages require exact naming, stable release metadata, SHA-256 evidence and Ed25519 detached-signature evidence, safe ZIP structure, a pre-update code backup, and deterministic local health validation.

No database migration or database rollback is introduced.

Release assets include `andy-core-v1.5.4.zip`, `SHA256.txt`, and `update-metadata.json`.
