# ANDY-CORE-v1.5.5-FIRST-NATIVE-UPGRADE-UAT

## Purpose

Prove the first real production-native Andy Core upgrade from the manually bootstrapped v1.5.4 updater to v1.5.5.

## Scope

- Product version 1.5.5.
- Database version remains 1.4.0.
- No Connector, business-runtime, schema, UI, or policy change.
- Signed private GitHub Release through the automated release pipeline.
- Production Owner UAT must exercise Check for Updates and WordPress native Update Now.

## Pass contract

The installed v1.5.4 instance discovers stable v1.5.5, authenticates to the private repository, validates SHA-256 and Ed25519 evidence, creates a bounded code backup, installs through WordPress native updater, and reports healthy v1.5.5 after installation. Any verification or post-install health failure must fail closed.
