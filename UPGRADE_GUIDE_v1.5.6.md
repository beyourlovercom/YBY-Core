# Andy Core v1.5.6 Upgrade Guide

Upgrade target: Andy Core 1.5.6
Database target: 1.4.0

Andy Core 1.5.6 is the bootstrap release for the public zero-config GitHub Release channel. Because installed 1.5.4/1.5.5 builds still point to the private source repository, Production must install 1.5.6 once through the existing manually authorized plugin upload/replace process.

After 1.5.6 is installed, no `YBY_CORE_GITHUB_TOKEN`, SSH change, or wp-config.php update credential is required. Future releases are discovered from `beyourlovercom/andy-core-release` and surfaced through WordPress native Plugins > Update Now.

Every update still requires stable release metadata, exact assets, SHA-256 agreement, pinned Ed25519 signature verification, compatible database metadata, safe ZIP structure, a bounded pre-update code backup, and post-install health validation.
