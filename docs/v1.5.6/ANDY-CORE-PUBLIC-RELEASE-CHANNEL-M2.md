# ANDY-CORE-PUBLIC-RELEASE-CHANNEL-M2

Target: Andy Core 1.5.6
Database: 1.4.0 unchanged

## Goal

Make Andy Core updates behave like normal WordPress plugin updates: install the bootstrap once, then use Plugins > Update Now with no GitHub token, SSH, or wp-config.php update credential.

## Canonical release split

- Private source: `beyourlovercom/YBY-Core`
- Public signed distribution: `beyourlovercom/andy-core-release`
- WordPress reads only the public distribution repository.
- Source code, branches, PRs, tests, and development history remain private.

## Security contract

Public download does not replace authenticity controls. Update acceptance still requires exact stable versioning, four unique required assets, SHA-256 agreement, pinned Ed25519 detached signature verification, database compatibility, safe ZIP structure, bounded code backup, post-install health validation, and rollback.

## Release automation

The private source workflow builds/signs with read-only source permissions, then a separate publish job uses a target-repository-only GitHub Actions credential to create a draft in `andy-core-release`, verifies its eight assets and package digest by Release ID, and only then publishes it stable/latest.
