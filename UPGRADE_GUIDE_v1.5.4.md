# Andy Core v1.5.4 Upgrade Guide

Upgrade target: Andy Core 1.5.4
Database target: 1.4.0

Andy Core 1.5.4 is the bootstrap release for the private secure updater. Because Andy Core 1.5.3 does not contain this updater, the first installation of 1.5.4 must still be performed through the existing manually authorized WordPress plugin upload/deployment process.

Before using secure updates after 1.5.4 is installed, define `YBY_CORE_GITHUB_TOKEN` in the WordPress runtime with read-only access to the private `beyourlovercom/YBY-Core` repository. The token is read at runtime; Andy Core does not save it to WordPress options/transients, render it, or log it.

For subsequent releases, use Andy Core > Settings > 鏇存柊 to refresh update information, then use WordPress's native Plugins > Update Now flow. Before WordPress receives an update ZIP, the updater requires a stable GitHub Release, the exact package asset, `SHA256.txt`, `update-metadata.json`, matching SHA-256 evidence and Ed25519 detached-signature evidence, compatible database version metadata, and a safe package structure. Immediately before installation it creates a bounded code backup outside public uploads.

After installation, Andy Core validates the installed target version and bootstrap files. If post-install health validation fails, the previous code backup is restored automatically. This release introduces no database migration and no database rollback.
