# Andy Core V2.2.3 Canary Rollback Guide

- Dev only; never roll back database with a code-only backup.
- The updater creates and verifies a protected previous-code backup before installation.
- On validation failure use the guarded automatic rollback; verify plugin version and module health.
- No WWW/Production modifications.
