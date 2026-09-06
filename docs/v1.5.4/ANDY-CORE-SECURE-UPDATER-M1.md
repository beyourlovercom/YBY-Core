# ANDY-CORE-SECURE-UPDATER-M1

Status: OWNER UAT PASS / PR PENDING
Target release: Andy Core 1.5.4
Database version: 1.4.0 (unchanged)
Base: `origin/main@7221bb9a73b145214c2301ed4b4f154e95da9c0d`

## Purpose

Bootstrap a fail-closed private GitHub Release updater so Andy Core 1.5.4 is the last release that requires the existing manually authorized installation path. Future stable releases can be surfaced through native WordPress plugin update UX.

## In scope

- private `beyourlovercom/YBY-Core` stable GitHub Release discovery;
- server-side `YBY_CORE_GITHUB_TOKEN` authentication with no WordPress persistence;
- native WordPress update transient and plugin-information integration;
- manual Check for Updates under Andy Core Settings;
- exact release assets: `andy-core-vX.Y.Z.zip`, `SHA256.txt`, `update-metadata.json`, `update-metadata.sig`;
- SHA-256 evidence and Ed25519 detached-signature evidence verification before the package is returned to WordPress;
- release metadata compatibility gate requiring database version `1.4.0` for M1;
- ZIP path/root/runtime-structure validation;
- pre-install code backup outside public uploads, maximum three retained backups;
- target-version post-install local health validation;
- automatic code restore on failed health;
- capability/nonce-protected manual code rollback;
- release build/CI metadata required by the updater;
- pinned Ed25519 public key in plugin code; the private signing key must exist only as the `ANDY_CORE_UPDATE_SIGNING_SECRET` release credential.

## Security invariants

- Authorization is sent only to `https://api.github.com` for the exact repository API namespace.
- Authorization is never forwarded to release-asset redirect hosts.
- Userinfo, non-default HTTPS ports, unapproved hosts, unexpected redirect chains, malformed asset URLs, duplicate required assets, malformed digest/checksum evidence, incompatible database metadata and unsafe ZIP paths fail closed.
- GitHub token, connector secrets, passwords, cookies and customer data are never stored in updater state, notices, logs or release evidence.
- Pending updater state contains only non-secret release/backup references needed to validate the target install.
- Code backup/restore rejects symlink traversal and only restores a validated direct child of the dedicated updater backup root.

## Explicit non-scope

- no database migration;
- no database rollback;
- no automatic GitHub Release publication;
- no Production deployment in this WP gate;
- no ERP domain behavior;
- no changes to the frozen v1.5.3 Connector contract except normal regression protection;
- no new frontend framework or top-level admin menu.

## Delivery gates

1. focused updater harness PASS;
2. PHP lint PASS;
3. Connector/security regression PASS;
4. release metadata/build/package validation PASS;
5. Local runtime sync from the exact WP worktree/source PASS;
6. Owner admin UX / rollback UAT PASS;
7. commit/push/PR/CI/merge;
8. tag/GitHub Release only under the release authorization gate;
9. first manual 1.5.4 Production installation remains a separate Production deployment gate.
